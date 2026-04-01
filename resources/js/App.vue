<template>
    <div class="relative min-h-screen overflow-hidden">
        <div
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(45,212,191,0.15),transparent)]"
        />
        <div
            class="pointer-events-none absolute -right-32 top-1/3 h-96 w-96 rounded-full bg-teal-500/10 blur-3xl"
        />
        <div class="relative mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
            <header class="mb-10 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-teal-400/90">Laravel · Vue</p>
                    <h1 class="mt-2 font-[DM_Sans,ui-sans-serif] text-3xl font-bold tracking-tight text-white sm:text-4xl">
                        Task desk
                    </h1>
                    <p class="mt-2 max-w-xl text-sm text-slate-400">
                        Create, sort, and advance tasks. Only completed work can be cleared from the board.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <label class="sr-only" for="status-filter">Filter by status</label>
                    <select
                        id="status-filter"
                        v-model="statusFilter"
                        class="rounded-lg border border-slate-700/80 bg-slate-900/80 px-3 py-2 text-sm text-slate-100 shadow-inner shadow-black/20 outline-none ring-teal-500/30 transition focus:border-teal-500/60 focus:ring-2"
                        @change="loadTasks"
                    >
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In progress</option>
                        <option value="done">Done</option>
                    </select>
                    <button
                        type="button"
                        class="rounded-lg border border-slate-600 bg-slate-800/80 px-3 py-2 text-sm font-medium text-slate-100 transition hover:border-teal-500/50 hover:bg-slate-800"
                        @click="loadTasks"
                    >
                        Refresh
                    </button>
                </div>
            </header>

            <div class="grid gap-8 lg:grid-cols-5">
                <section class="lg:col-span-2">
                    <div class="rounded-2xl border border-slate-800/80 bg-slate-900/40 p-6 shadow-xl shadow-black/40 backdrop-blur">
                        <h2 class="font-[DM_Sans,ui-sans-serif] text-lg font-semibold text-white">New task</h2>
                        <p class="mt-1 text-xs text-slate-500">Title must be unique for the chosen due date.</p>
                        <form class="mt-5 space-y-4" @submit.prevent="createTask">
                            <div>
                                <label class="block text-xs font-medium text-slate-400" for="title">Title</label>
                                <input
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    required
                                    class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-sm text-white outline-none ring-teal-500/20 focus:border-teal-500/60 focus:ring-2"
                                    placeholder="Ship the report"
                                />
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-medium text-slate-400" for="due">Due date</label>
                                    <input
                                        id="due"
                                        v-model="form.due_date"
                                        type="date"
                                        required
                                        class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-sm text-white outline-none focus:border-teal-500/60 focus:ring-2 focus:ring-teal-500/30"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-400" for="priority">Priority</label>
                                    <select
                                        id="priority"
                                        v-model="form.priority"
                                        required
                                        class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-sm text-white outline-none focus:border-teal-500/60 focus:ring-2 focus:ring-teal-500/30"
                                    >
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                            </div>
                            <p v-if="formError" class="text-sm text-rose-400">{{ formError }}</p>
                            <button
                                type="submit"
                                class="w-full rounded-lg bg-teal-500 px-4 py-2.5 text-sm font-semibold text-slate-950 shadow-lg shadow-teal-500/20 transition hover:bg-teal-400"
                                :disabled="creating"
                            >
                                {{ creating ? 'Creating…' : 'Create task' }}
                            </button>
                        </form>
                    </div>

                    <div class="mt-6 rounded-2xl border border-slate-800/80 bg-slate-900/40 p-6 backdrop-blur">
                        <h2 class="font-[DM_Sans,ui-sans-serif] text-lg font-semibold text-white">Daily report</h2>
                        <p class="mt-1 text-xs text-slate-500">Counts by priority and status for tasks due on a date.</p>
                        <div class="mt-4 flex flex-wrap items-end gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-400" for="report-date">Date</label>
                                <input
                                    id="report-date"
                                    v-model="reportDate"
                                    type="date"
                                    class="mt-1 rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-sm text-white outline-none focus:border-teal-500/60 focus:ring-2 focus:ring-teal-500/30"
                                />
                            </div>
                            <button
                                type="button"
                                class="rounded-lg border border-slate-600 bg-slate-800 px-3 py-2 text-sm font-medium text-slate-100 hover:border-teal-500/50"
                                @click="loadReport"
                            >
                                Load report
                            </button>
                        </div>
                        <div v-if="report" class="mt-6 space-y-4">
                            <p class="text-xs uppercase tracking-wider text-slate-500">Date: {{ report.date }}</p>
                            <div class="overflow-x-auto rounded-xl border border-slate-800">
                                <table class="min-w-full divide-y divide-slate-800 text-sm">
                                    <thead class="bg-slate-950/50">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-medium text-slate-400">Priority</th>
                                            <th class="px-3 py-2 text-right text-slate-400">Pending</th>
                                            <th class="px-3 py-2 text-right text-slate-400">In progress</th>
                                            <th class="px-3 py-2 text-right text-slate-400">Done</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800/80">
                                        <tr v-for="p in ['high', 'medium', 'low']" :key="p">
                                            <td class="px-3 py-2 capitalize text-slate-200">{{ p }}</td>
                                            <td class="px-3 py-2 text-right tabular-nums text-slate-300">
                                                {{ report.summary[p].pending }}
                                            </td>
                                            <td class="px-3 py-2 text-right tabular-nums text-slate-300">
                                                {{ report.summary[p].in_progress }}
                                            </td>
                                            <td class="px-3 py-2 text-right tabular-nums text-slate-300">
                                                {{ report.summary[p].done }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="lg:col-span-3">
                    <div class="rounded-2xl border border-slate-800/80 bg-slate-900/30 p-6 shadow-inner shadow-black/30">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="font-[DM_Sans,ui-sans-serif] text-lg font-semibold text-white">Board</h2>
                            <span class="rounded-full border border-slate-700/80 bg-slate-950/60 px-3 py-1 text-xs text-slate-400">
                                Sorted: high → medium → low, then due date
                            </span>
                        </div>

                        <p v-if="listMessage" class="mt-6 rounded-lg border border-dashed border-slate-700 bg-slate-950/40 px-4 py-6 text-center text-sm text-slate-400">
                            {{ listMessage }}
                        </p>

                        <ul v-else class="mt-6 space-y-4">
                            <li
                                v-for="task in tasks"
                                :key="task.id"
                                class="rounded-xl border border-slate-800 bg-slate-950/50 p-4 transition hover:border-teal-500/30"
                            >
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p class="font-medium text-white">{{ task.title }}</p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            Due {{ task.due_date }} ·
                                            <span class="capitalize text-slate-400">{{ task.priority }}</span>
                                        </p>
                                        <span
                                            class="mt-2 inline-flex rounded-md border px-2 py-0.5 text-xs font-medium capitalize"
                                            :class="statusClass(task.status)"
                                        >
                                            {{ humanStatus(task.status) }}
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-if="task.status === 'pending'"
                                            type="button"
                                            class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-medium text-teal-200 ring-1 ring-slate-600 hover:ring-teal-500/50"
                                            @click="advance(task, 'in_progress')"
                                        >
                                            Start
                                        </button>
                                        <button
                                            v-if="task.status === 'in_progress'"
                                            type="button"
                                            class="rounded-lg bg-teal-600/90 px-3 py-1.5 text-xs font-semibold text-slate-950 hover:bg-teal-500"
                                            @click="advance(task, 'done')"
                                        >
                                            Complete
                                        </button>
                                        <button
                                            v-if="task.status === 'done'"
                                            type="button"
                                            class="rounded-lg border border-rose-500/40 bg-rose-950/40 px-3 py-1.5 text-xs font-medium text-rose-200 hover:border-rose-400/60"
                                            @click="remove(task)"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                                <p v-if="taskErrors[task.id]" class="mt-2 text-xs text-rose-400">{{ taskErrors[task.id] }}</p>
                            </li>
                        </ul>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

const tasks = ref([]);
const listMessage = ref('');
const statusFilter = ref('');
const creating = ref(false);
const formError = ref('');
const taskErrors = reactive({});
const report = ref(null);
const reportDate = ref(new Date().toISOString().slice(0, 10));

const form = reactive({
    title: '',
    due_date: new Date().toISOString().slice(0, 10),
    priority: 'medium',
});

function humanStatus(s) {
    if (s === 'in_progress') return 'In progress';
    return s;
}

function statusClass(s) {
    if (s === 'done') return 'border-emerald-500/40 bg-emerald-500/10 text-emerald-200';
    if (s === 'in_progress') return 'border-amber-500/40 bg-amber-500/10 text-amber-100';
    return 'border-slate-600 bg-slate-800/80 text-slate-300';
}

async function loadTasks() {
    listMessage.value = '';
    formError.value = '';
    const params = {};
    if (statusFilter.value) params.status = statusFilter.value;
    try {
        const { data } = await api.get('/tasks', { params });
        if (data.message && Array.isArray(data.data) && data.data.length === 0) {
            tasks.value = [];
            listMessage.value = data.message;
            return;
        }
        tasks.value = data.data ?? [];
        if (!tasks.value.length) {
            listMessage.value = 'No tasks to show.';
        }
    } catch (e) {
        listMessage.value = e.response?.data?.message ?? 'Could not load tasks.';
        tasks.value = [];
    }
}

async function createTask() {
    creating.value = true;
    formError.value = '';
    try {
        await api.post('/tasks', { ...form });
        await loadTasks();
        form.title = '';
    } catch (e) {
        const errs = e.response?.data?.errors;
        if (errs) {
            formError.value = Object.values(errs).flat().join(' ');
        } else {
            formError.value = e.response?.data?.message ?? 'Could not create task.';
        }
    } finally {
        creating.value = false;
    }
}

async function advance(task, next) {
    taskErrors[task.id] = '';
    try {
        await api.patch(`/tasks/${task.id}/status`, { status: next });
        await loadTasks();
    } catch (e) {
        const msg = e.response?.data?.errors?.status?.[0] ?? e.response?.data?.message ?? 'Invalid status change.';
        taskErrors[task.id] = msg;
    }
}

async function remove(task) {
    taskErrors[task.id] = '';
    try {
        await api.delete(`/tasks/${task.id}`);
        await loadTasks();
        await loadReport();
    } catch (e) {
        taskErrors[task.id] = e.response?.data?.message ?? 'Could not delete.';
    }
}

async function loadReport() {
    try {
        const { data } = await api.get('/tasks/report', { params: { date: reportDate.value } });
        report.value = data;
    } catch {
        report.value = null;
    }
}

onMounted(() => {
    loadTasks();
    loadReport();
});
</script>
