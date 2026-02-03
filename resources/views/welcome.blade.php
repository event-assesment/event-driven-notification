<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Event-Driven Notifications') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=fraunces:400,500,600,700|work-sans:300,400,500,600,700" rel="stylesheet" />

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

        <style>
            :root {
                --font-display: 'Fraunces', serif;
                --font-body: 'Work Sans', system-ui, sans-serif;
            }
            .noise {
                background-image: radial-gradient(circle at 1px 1px, rgba(14, 18, 17, 0.05) 1px, transparent 0);
                background-size: 14px 14px;
            }
        </style>
    </head>
    <body class="min-h-screen bg-[#f5f1ea] text-slate-900">
        <div class="absolute inset-0 -z-10">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(14,116,144,0.18),_transparent_52%)]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_80%,_rgba(251,191,36,0.2),_transparent_48%)]"></div>
            <div class="absolute inset-0 noise"></div>
        </div>

        <main class="mx-auto flex min-h-screen max-w-screen-2xl flex-col gap-10 px-6 pb-16 pt-10 lg:px-10">
            <header class="flex flex-col gap-6 rounded-3xl border border-black/10 bg-white/70 p-8 shadow-xl shadow-black/5 backdrop-blur">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-col gap-3">
                        <p class="text-xs uppercase tracking-[0.35em] text-slate-500">Realtime Command</p>
                        <h1 class="text-4xl font-semibold leading-tight text-slate-950 lg:text-5xl" style="font-family: var(--font-display)">
                            Event-Driven Notification Console
                        </h1>
                        <p class="max-w-2xl text-base text-slate-700" style="font-family: var(--font-body)">
                            Playground for sending and managing notifications via our event-driven notification system.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <a class="inline-flex items-center gap-2 rounded-full border border-slate-900/10 bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-black/10 transition hover:-translate-y-0.5" href="/api/documentation" target="_blank" rel="noopener">
                            Swagger Docs
                        </a>
                        <button id="open-create-modal" class="inline-flex items-center gap-2 rounded-full border border-emerald-600/30 bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-600/20 transition hover:-translate-y-0.5" type="button">
                            New Notification
                        </button>
                        <button id="open-batch-modal" class="inline-flex items-center gap-2 rounded-full border border-slate-900/10 bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:-translate-y-0.5" type="button">
                            Send Batch
                        </button>
                    </div>
                </div>
            </header>

            <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
                <div class="rounded-3xl border border-slate-900/10 bg-white/80 p-6 shadow-xl shadow-black/5 backdrop-blur">
                    <div class="flex flex-col gap-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div>
                                <h2 class="text-2xl font-semibold" style="font-family: var(--font-display)">Notification Table</h2>
                                <p class="text-sm text-slate-600">Paginated list with live status updates.</p>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <button id="refresh-list" class="rounded-full border border-slate-900/10 bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:-translate-y-0.5" type="button">
                                    Refresh
                                </button>
                                <button id="open-templates-modal" class="rounded-full border border-slate-900/10 bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:-translate-y-0.5" type="button">
                                    Templates
                                </button>
                            </div>
                        </div>

                        <form id="filter-form" class="grid gap-4 rounded-2xl border border-slate-900/10 bg-slate-50/70 p-4 lg:grid-cols-7">
                            <div class="grid gap-2">
                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Status</label>
                                <select name="status" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                                    <option value="">All</option>
                                    <option value="queued">queued</option>
                                    <option value="accepted">accepted</option>
                                    <option value="delivered">delivered</option>
                                    <option value="failed">failed</option>
                                    <option value="scheduled">scheduled</option>
                                    <option value="canceled">canceled</option>
                                    <option value="unknown">unknown</option>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Channel</label>
                                <select name="channel" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                                    <option value="">All</option>
                                    <option value="sms">sms</option>
                                    <option value="email">email</option>
                                    <option value="push">push</option>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Priority</label>
                                <select name="priority" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                                    <option value="">All</option>
                                    <option value="high">high</option>
                                    <option value="normal">normal</option>
                                    <option value="low">low</option>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Created from</label>
                                <input name="created_from" data-datepicker class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" placeholder="Select date">
                            </div>
                            <div class="grid gap-2">
                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Created to</label>
                                <input name="created_to" data-datepicker class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" placeholder="Select date">
                            </div>
                            <div class="grid gap-2">
                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Per page</label>
                                <select name="per_page" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                                    <option value="10">10 / page</option>
                                    <option value="15" selected>15 / page</option>
                                    <option value="25">25 / page</option>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Reset</label>
                                <button id="reset-filters" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:-translate-y-0.5" type="button">
                                    Reset filters
                                </button>
                            </div>
                        </form>

                        <div class="overflow-x-auto rounded-2xl border border-slate-900/10">
                            <table class="min-w-[1040px] w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-900 text-white">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs uppercase tracking-[0.2em]">Recipient</th>
                                        <th class="px-4 py-3 text-left text-xs uppercase tracking-[0.2em]">Channel</th>
                                        <th class="px-4 py-3 text-left text-xs uppercase tracking-[0.2em]">Status</th>
                                        <th class="px-4 py-3 text-left text-xs uppercase tracking-[0.2em]">Priority</th>
                                        <th class="px-4 py-3 text-left text-xs uppercase tracking-[0.2em]">Created</th>
                                        <th class="px-4 py-3 text-left text-xs uppercase tracking-[0.2em]">Updated</th>
                                        <th class="px-4 py-3 text-right text-xs uppercase tracking-[0.2em]">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="notification-table" class="divide-y divide-slate-100 bg-white">
                                </tbody>
                            </table>
                        </div>

                        <div class="flex flex-col items-center justify-between gap-4 rounded-2xl border border-slate-900/10 bg-white px-4 py-3 text-sm lg:flex-row">
                            <div id="pagination-meta" class="text-slate-600">Loading...</div>
                            <div class="flex items-center gap-3">
                                <button id="prev-page" class="rounded-full border border-slate-900/10 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-slate-700 transition hover:-translate-y-0.5" type="button">Prev</button>
                                <button id="next-page" class="rounded-full border border-slate-900/10 bg-slate-900 px-4 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-white transition hover:-translate-y-0.5" type="button">Next</button>
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="flex flex-col gap-6">
                    <div class="rounded-3xl border border-slate-900/10 bg-white/80 p-6 shadow-xl shadow-black/5 backdrop-blur">
                        <h2 class="text-2xl font-semibold" style="font-family: var(--font-display)">Event Stream</h2>
                        <p class="text-sm text-slate-600">Live stream per batch.</p>
                        <div class="mt-4 rounded-2xl border border-slate-900/10 bg-slate-900 p-4 text-xs text-emerald-200" style="min-height: 220px;">
                            <div id="ws-log" class="flex max-h-60 flex-col gap-2 overflow-auto"></div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-900/10 bg-white/80 p-6 shadow-xl shadow-black/5 backdrop-blur">
                        <div class="flex items-center justify-between">
                            <h2 class="text-2xl font-semibold" style="font-family: var(--font-display)">Operations</h2>
                        </div>
                        <div class="mt-4 grid gap-4">
                            <button id="fetch-metrics" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800" type="button">Fetch Metrics</button>
                            <pre id="metrics-output" class="max-h-40 overflow-auto rounded-xl bg-slate-900 p-3 text-xs text-emerald-200"></pre>
                            <button id="fetch-health" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:-translate-y-0.5" type="button">Health Check</button>
                            <pre id="health-output" class="max-h-24 overflow-auto rounded-xl bg-slate-900 p-3 text-xs text-emerald-200"></pre>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-900/10 bg-white/80 p-6 shadow-xl shadow-black/5 backdrop-blur">
                        <h2 class="text-2xl font-semibold" style="font-family: var(--font-display)">Request Log</h2>
                        <p class="text-sm text-slate-600">Last 3 request results.</p>
                        <div id="request-log" class="mt-4 grid gap-3"></div>
                    </div>
                </aside>
            </section>
        </main>

        <div id="modal-backdrop" class="fixed inset-0 z-40 hidden bg-slate-900/50 backdrop-blur"></div>

        <div id="create-modal" class="fixed inset-0 z-50 hidden items-center justify-center">
            <div class="w-full max-w-2xl rounded-3xl border border-slate-900/10 bg-white p-6 shadow-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-2xl font-semibold" style="font-family: var(--font-display)">New Notification</h3>
                    <button class="close-modal rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600" type="button">Close</button>
                </div>
                <form id="create-notification-form" class="mt-4 grid gap-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-2">
                            <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Recipient</label>
                            <input class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" name="to" placeholder="Recipient" required>
                        </div>
                        <div class="grid gap-2">
                            <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Idempotency key</label>
                            <input class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" name="idempotency_key" placeholder="Idempotency-Key">
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="grid gap-2">
                            <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Channel</label>
                            <select class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" name="channel">
                                <option value="sms">sms</option>
                                <option value="email">email</option>
                                <option value="push">push</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Priority</label>
                            <select class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" name="priority">
                                <option value="normal">normal</option>
                                <option value="high">high</option>
                                <option value="low">low</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Scheduled at</label>
                            <input class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" name="scheduled_at" data-datepicker placeholder="Pick date/time">
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Content</label>
                        <textarea class="min-h-[100px] rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" name="content" placeholder="Content"></textarea>
                    </div>
                    <div class="grid gap-3">
                        <div class="grid gap-2">
                            <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Template ID</label>
                            <select class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" name="template_id" data-template-select>
                                <option value="">Loading templates...</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <div class="flex items-center justify-between">
                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Variables</label>
                                <button class="rounded-full border border-slate-900/10 bg-white px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 transition hover:-translate-y-0.5" type="button" data-add-variables="notification-variables">
                                    + Add
                                </button>
                            </div>
                            <div id="notification-variables" class="grid gap-2 rounded-2xl border border-slate-200 bg-white/80 p-3"></div>
                            <p class="text-[11px] text-slate-400">Optional key/value pairs for template variables.</p>
                        </div>
                    </div>
                    <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white" type="submit">Send</button>
                </form>
                <pre id="create-notification-output" class="mt-4 max-h-40 overflow-auto rounded-xl bg-slate-900 p-3 text-xs text-emerald-200"></pre>
            </div>
        </div>

        <div id="batch-modal" class="fixed inset-0 z-50 hidden items-center justify-center">
            <div class="w-full max-w-2xl rounded-3xl border border-slate-900/10 bg-white p-6 shadow-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-2xl font-semibold" style="font-family: var(--font-display)">Batch Notifications</h3>
                    <button class="close-modal rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600" type="button">Close</button>
                </div>
                <form id="batch-notification-form" class="mt-4 grid gap-3">
                    <div class="grid gap-2">
                        <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Payload</label>
                        <textarea class="min-h-[180px] rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" name="payload" placeholder='{"notifications": [{"to": "user@example.com", "channel": "email", "content": "Hello"}]}'></textarea>
                    </div>
                    <div class="grid gap-2">
                        <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Idempotency key</label>
                        <input class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" name="idempotency_key" placeholder="Idempotency-Key">
                    </div>
                    <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white" type="submit">Send</button>
                </form>
                <pre id="batch-notification-output" class="mt-4 max-h-40 overflow-auto rounded-xl bg-slate-900 p-3 text-xs text-emerald-200"></pre>
            </div>
        </div>

        <div id="templates-modal" class="fixed inset-0 z-50 hidden items-center justify-center">
            <div class="w-full max-w-5xl rounded-3xl border border-slate-900/10 bg-white p-6 shadow-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-2xl font-semibold" style="font-family: var(--font-display)">Templates</h3>
                    <button class="close-modal rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600" type="button">Close</button>
                </div>
                <div class="mt-4 grid gap-6 lg:grid-cols-[280px_minmax(0,1fr)]">
                    <div class="rounded-2xl border border-slate-900/10 bg-slate-50/70 p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Templates</p>
                            <button id="template-reset" class="rounded-full border border-slate-900/10 bg-white px-3 py-1 text-xs font-semibold text-slate-700 transition hover:-translate-y-0.5" type="button">
                                New
                            </button>
                        </div>
                        <div id="template-list" class="mt-3 grid max-h-80 gap-2 overflow-auto pr-1"></div>
                    </div>
                    <div class="grid gap-3">
                        <form id="template-form" class="grid gap-3">
                            <input type="hidden" name="template_id">
                            <div class="flex items-center justify-between">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Template details</p>
                                <span id="template-form-mode" class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">New</span>
                            </div>
                            <div class="grid gap-2">
                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Name</label>
                                <input class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" name="name" placeholder="Template name" required>
                            </div>
                            <div class="grid gap-2">
                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Channel</label>
                                <select class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" name="channel">
                                    <option value="sms">sms</option>
                                    <option value="email">email</option>
                                    <option value="push">push</option>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Body</label>
                                <textarea class="min-h-[140px] rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" name="body" placeholder="Hello @{{ $name }}" required></textarea>
                            </div>
                            <div class="grid gap-2">
                                <div class="flex items-center justify-between">
                                    <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Sample variables</label>
                                    <button class="rounded-full border border-slate-900/10 bg-white px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500 transition hover:-translate-y-0.5" type="button" data-add-variables="template-sample-variables">
                                        + Add
                                    </button>
                                </div>
                                <div id="template-sample-variables" class="grid gap-2 rounded-2xl border border-slate-200 bg-white/80 p-3"></div>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <button id="template-preview-button" class="rounded-xl border border-slate-900/10 bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:-translate-y-0.5" type="button">Preview</button>
                                <button id="template-save-button" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white" type="submit">Create Template</button>
                                <button id="template-delete-button" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-600 transition hover:-translate-y-0.5" type="button" disabled>Delete</button>
                            </div>
                        </form>
                        <div class="grid gap-2 rounded-2xl border border-slate-900/10 bg-slate-50/70 p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Preview</p>
                            <pre id="template-preview-output" class="max-h-40 overflow-auto rounded-xl bg-slate-900 p-3 text-xs text-emerald-200"></pre>
                        </div>
                        <pre id="template-form-output" class="max-h-32 overflow-auto rounded-xl bg-slate-900 p-3 text-xs text-emerald-200"></pre>
                    </div>
                </div>
            </div>
        </div>

        <div id="callback-modal" class="fixed inset-0 z-50 hidden items-center justify-center">
            <div class="w-full max-w-xl rounded-3xl border border-slate-900/10 bg-white p-6 shadow-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-2xl font-semibold" style="font-family: var(--font-display)">Provider Callback</h3>
                    <button class="close-modal rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600" type="button">Close</button>
                </div>
                <form id="provider-callback-form" class="mt-4 grid gap-3">
                    <div class="grid gap-2">
                        <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Provider</label>
                        <input class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" name="provider" value="webhook_site" required>
                    </div>
                    <div class="grid gap-2">
                        <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Message ID</label>
                        <input class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" name="message_id" placeholder="message_id" required>
                    </div>
                    <div class="grid gap-2">
                        <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Status</label>
                        <select class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" name="status">
                            <option value="delivered">delivered</option>
                            <option value="failed">failed</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-2">
                            <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Error code</label>
                            <input class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" name="error_code" placeholder="error code">
                        </div>
                        <div class="grid gap-2">
                            <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Error message</label>
                            <input class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" name="error_message" placeholder="error message">
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <label class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Timestamp</label>
                        <input class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" name="timestamp" data-datepicker placeholder="Timestamp">
                    </div>
                    <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white" type="submit">Trigger</button>
                </form>
                <pre id="provider-callback-output" class="mt-4 max-h-40 overflow-auto rounded-xl bg-slate-900 p-3 text-xs text-emerald-200"></pre>
            </div>
        </div>

        <div id="details-panel" class="fixed right-6 top-20 z-40 hidden w-full max-w-md rounded-3xl border border-slate-900/10 bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-semibold" style="font-family: var(--font-display)">Notification Detail</h3>
                <button id="close-details" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600" type="button">Close</button>
            </div>
            <pre id="details-output" class="mt-4 max-h-96 overflow-auto rounded-xl bg-slate-900 p-3 text-xs text-emerald-200"></pre>
        </div>

        <div id="toast-container" class="pointer-events-none fixed right-6 top-6 z-[60] flex max-w-sm flex-col gap-2"></div>

        <script>
            const reverbConfig = @json(config('broadcasting.connections.reverb'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const baseHeaders = {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            };

            const togglePreVisibility = () => {
                document.querySelectorAll('pre').forEach((pre) => {
                    const isEmpty = pre.textContent.trim() === '';
                    pre.classList.toggle('hidden', isEmpty);
                });
            };

            const extractApiMessage = (payload) => {
                if (!payload || typeof payload !== 'object') {
                    return null;
                }

                if (typeof payload.message === 'string') {
                    return payload.message;
                }

                if (typeof payload.error === 'string') {
                    return payload.error;
                }

                if (Array.isArray(payload.errors) && payload.errors.length > 0) {
                    return payload.errors[0];
                }

                if (payload.errors && typeof payload.errors === 'object') {
                    const firstKey = Object.keys(payload.errors)[0];
                    if (firstKey && Array.isArray(payload.errors[firstKey]) && payload.errors[firstKey][0]) {
                        return payload.errors[firstKey][0];
                    }
                }

                return null;
            };

            const showToast = (message, type = 'error') => {
                if (!message) {
                    return;
                }

                const container = document.getElementById('toast-container');
                if (!container) {
                    return;
                }

                const toast = document.createElement('div');
                const palette = {
                    error: 'border-rose-200 bg-rose-50 text-rose-700',
                    success: 'border-emerald-200 bg-emerald-50 text-emerald-700',
                };

                toast.className = `pointer-events-auto max-w-sm break-words rounded-2xl border px-4 py-3 text-sm font-semibold shadow-lg shadow-black/5 transition ${palette[type] ?? palette.error}`;
                toast.textContent = message;
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-6px)';
                toast.style.display = '-webkit-box';
                toast.style.webkitLineClamp = '3';
                toast.style.webkitBoxOrient = 'vertical';
                toast.style.overflow = 'hidden';

                container.prepend(toast);

                requestAnimationFrame(() => {
                    toast.style.opacity = '1';
                    toast.style.transform = 'translateY(0)';
                });

                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(-6px)';
                    setTimeout(() => toast.remove(), 220);
                }, 4200);
            };

            const logOutput = (elementId, payload) => {
                const el = document.getElementById(elementId);
                if (!el) {
                    return;
                }
                el.textContent = JSON.stringify(payload, null, 2);
                togglePreVisibility();
            };

            const pushRequestLog = (payload) => {
                const log = document.getElementById('request-log');
                if (!log) {
                    return;
                }
                const card = document.createElement('div');
                card.className = 'rounded-xl border border-slate-900/10 bg-white px-4 py-3 text-xs text-slate-600';
                card.textContent = `${new Date().toLocaleTimeString()} | ${payload.method} ${payload.url} (${payload.status})`;
                log.prepend(card);
                const items = log.querySelectorAll('div');
                if (items.length > 3) {
                    items[items.length - 1].remove();
                }
            };

            const requestJson = async (url, method = 'GET', body = null, headers = {}) => {
                const options = {
                    method,
                    headers: {
                        ...baseHeaders,
                        ...headers,
                    },
                };

                if (body !== null) {
                    options.headers['Content-Type'] = 'application/json';
                    options.body = JSON.stringify(body);
                }

                const response = await fetch(url, options);
                const responseBody = await response.json().catch(() => ({}));

                pushRequestLog({ url, method, status: response.status });

                if (!response.ok) {
                    const message = extractApiMessage(responseBody) ?? `${response.status} ${response.statusText}`;
                    showToast(message, 'error');
                }

                return {
                    status: response.status,
                    ok: response.ok,
                    data: responseBody,
                };
            };

            const parseJsonInput = (value, outputId) => {
                if (!value) {
                    return null;
                }

                try {
                    return JSON.parse(value);
                } catch (error) {
                    logOutput(outputId, {
                        ok: false,
                        error: 'Invalid JSON payload.',
                        message: error.message,
                    });
                    return undefined;
                }
            };

            const formatDateTime = (value) => {
                if (!value) {
                    return '-';
                }
                const date = new Date(value);
                if (Number.isNaN(date.getTime())) {
                    return value;
                }
                return date.toLocaleString(undefined, {
                    year: 'numeric',
                    month: 'short',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                });
            };

            const toIsoFromLocal = (value) => {
                if (!value) {
                    return undefined;
                }
                const date = new Date(value);
                if (Number.isNaN(date.getTime())) {
                    return undefined;
                }
                return date.toISOString();
            };

            let echoInstance = null;
            let subscribedBatches = new Set();

            const appendLog = (message) => {
                const log = document.getElementById('ws-log');
                if (!log) {
                    return;
                }
                const row = document.createElement('div');
                row.className = 'rounded-lg bg-white/10 px-3 py-2 text-emerald-100';
                row.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
                log.prepend(row);
            };

            const ensureEcho = () => {
                if (echoInstance) {
                    return echoInstance;
                }

                if (window.Echo && typeof window.Echo.channel === 'function') {
                    echoInstance = window.Echo;
                    return echoInstance;
                }

                window.Pusher = Pusher;
                const EchoConstructor = window.Echo?.default ?? window.Echo;

                if (typeof EchoConstructor !== 'function') {
                    appendLog('Echo library not loaded.');
                    return null;
                }

                echoInstance = new EchoConstructor({
                    broadcaster: 'pusher',
                    key: reverbConfig.key,
                    wsHost: window.location.hostname,
                    wsPort: reverbConfig.options.port || 6001,
                    wssPort: reverbConfig.options.port || 6001,
                    forceTLS: window.location.protocol === 'https:',
                    enabledTransports: ['ws', 'wss'],
                    disableStats: true,
                });
                return echoInstance;
            };

            const subscribeToBatch = (batchId) => {
                if (!batchId || subscribedBatches.has(batchId)) {
                    return;
                }
                const echo = ensureEcho();
                if (!echo) {
                    return;
                }
                const channelName = `batch.${batchId}`;
                subscribedBatches.add(batchId);

                echo.channel(channelName)
                    .listen('.NotificationStatusChanged', (event) => {
                        appendLog(`Status: ${event.status} | ${event.channel} | ${event.id}`);
                        const row = document.querySelector(`[data-notification-id="${event.id}"]`);
                        if (row) {
                            row.querySelector('[data-status]')?.replaceWith(buildStatusBadge(event.status));
                            row.querySelector('[data-updated-at]').textContent = formatDateTime(event.updated_at);
                        }
                    });
            };

            const resetSubscriptions = (batchIds) => {
                const echo = ensureEcho();
                if (!echo) {
                    return;
                }
                subscribedBatches.forEach((batchId) => {
                    if (!batchIds.has(batchId)) {
                        echo.leave(`batch.${batchId}`);
                        subscribedBatches.delete(batchId);
                    }
                });

                batchIds.forEach((batchId) => subscribeToBatch(batchId));
            };

            const buildStatusBadge = (status) => {
                const span = document.createElement('span');
                const normalized = status ?? 'unknown';
                const palette = {
                    delivered: 'bg-emerald-100 text-emerald-700',
                    accepted: 'bg-amber-100 text-amber-700',
                    queued: 'bg-slate-200 text-slate-700',
                    failed: 'bg-rose-100 text-rose-700',
                    canceled: 'bg-slate-100 text-slate-500',
                    scheduled: 'bg-indigo-100 text-indigo-700',
                    unknown: 'bg-slate-200 text-slate-600',
                    sending: 'bg-blue-100 text-blue-700',
                    pending: 'bg-slate-200 text-slate-600',
                };

                span.className = `inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ${palette[normalized] ?? palette.unknown}`;
                span.dataset.status = normalized;
                span.dataset.status = normalized;
                span.dataset.status = normalized;
                span.setAttribute('data-status', '');
                span.textContent = normalized;
                return span;
            };

            const renderTemplateSelects = () => {
                document.querySelectorAll('[data-template-select]').forEach((select) => {
                    const currentValue = select.value;
                    select.innerHTML = '';

                    const emptyOption = document.createElement('option');
                    emptyOption.value = '';
                    emptyOption.textContent = state.templates.length ? 'No template' : 'No templates available';
                    select.appendChild(emptyOption);

                    state.templates.forEach((template) => {
                        const option = document.createElement('option');
                        option.value = template.id;
                        option.textContent = `${template.name} (${template.channel})`;
                        select.appendChild(option);
                    });

                    if (currentValue) {
                        select.value = currentValue;
                    }
                });
            };

            const buildVariableRow = (targetList, key = '', value = '') => {
                if (!targetList) {
                    return;
                }

                const row = document.createElement('div');
                row.className = 'flex flex-wrap items-center gap-2';
                row.setAttribute('data-variable-row', '');

                row.innerHTML = `
                    <input class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm sm:flex-1" data-variable-key placeholder="Key">
                    <input class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm sm:flex-1" data-variable-value placeholder="Value">
                    <button class="rounded-full border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:-translate-y-0.5 sm:ml-auto" type="button" data-variable-remove>
                        Remove
                    </button>
                `;

                row.querySelector('[data-variable-key]').value = key;
                row.querySelector('[data-variable-value]').value = value;
                row.querySelector('[data-variable-remove]').addEventListener('click', () => {
                    row.remove();
                });

                targetList.appendChild(row);
            };

            const resetVariableList = (targetList) => {
                if (!targetList) {
                    return;
                }
                targetList.innerHTML = '';
                buildVariableRow(targetList);
            };

            const collectVariables = (targetList) => {
                const variables = {};
                if (!targetList) {
                    return variables;
                }

                targetList.querySelectorAll('[data-variable-row]').forEach((row) => {
                    const key = row.querySelector('[data-variable-key]')?.value?.trim() ?? '';
                    const value = row.querySelector('[data-variable-value]')?.value ?? '';

                    if (key !== '') {
                        variables[key] = value;
                    }
                });

                return variables;
            };

            const updateTemplateFormActions = () => {
                const modeLabel = document.getElementById('template-form-mode');
                const deleteButton = document.getElementById('template-delete-button');
                const saveButton = document.getElementById('template-save-button');
                const isEditing = Boolean(state.selectedTemplateId);

                if (modeLabel) {
                    modeLabel.textContent = isEditing ? 'Editing' : 'New';
                }
                if (saveButton) {
                    saveButton.textContent = isEditing ? 'Update Template' : 'Create Template';
                }
                if (deleteButton) {
                    deleteButton.disabled = !isEditing;
                    deleteButton.classList.toggle('opacity-50', !isEditing);
                    deleteButton.classList.toggle('cursor-not-allowed', !isEditing);
                }
            };

            const applyTemplateToForm = (template) => {
                const form = document.getElementById('template-form');
                if (!form || !template) {
                    return;
                }

                form.template_id.value = template.id;
                form.name.value = template.name;
                form.channel.value = template.channel;
                form.body.value = template.body;
                state.selectedTemplateId = template.id;
                const previewOutput = document.getElementById('template-preview-output');
                if (previewOutput) {
                    previewOutput.textContent = '';
                    togglePreVisibility();
                }
                updateTemplateFormActions();
                renderTemplateList();
            };

            const resetTemplateForm = () => {
                const form = document.getElementById('template-form');
                if (!form) {
                    return;
                }

                form.reset();
                form.template_id.value = '';
                state.selectedTemplateId = null;
                resetVariableList(document.getElementById('template-sample-variables'));
                const previewOutput = document.getElementById('template-preview-output');
                if (previewOutput) {
                    previewOutput.textContent = '';
                    togglePreVisibility();
                }
                updateTemplateFormActions();
                renderTemplateList();
            };

            const renderTemplateList = () => {
                const list = document.getElementById('template-list');
                if (!list) {
                    return;
                }

                list.innerHTML = '';

                if (state.templates.length === 0) {
                    const empty = document.createElement('div');
                    empty.className = 'rounded-xl border border-dashed border-slate-200 bg-white px-3 py-2 text-xs text-slate-500';
                    empty.textContent = 'No templates found.';
                    list.appendChild(empty);
                    return;
                }

                state.templates.forEach((template) => {
                    const isActive = template.id === state.selectedTemplateId;
                    const card = document.createElement('button');
                    card.type = 'button';
                    card.className = [
                        'flex w-full flex-col gap-1 rounded-xl border px-3 py-2 text-left text-sm transition',
                        isActive ? 'border-slate-900 bg-white shadow-sm' : 'border-slate-200 bg-white/70 hover:-translate-y-0.5',
                    ].join(' ');
                    card.dataset.templateId = template.id;
                    card.innerHTML = `
                        <span class="text-sm font-semibold text-slate-900">${template.name}</span>
                        <span class="text-xs text-slate-500">${template.channel} - ${template.id.slice(0, 8)}...</span>
                    `;
                    card.addEventListener('click', () => applyTemplateToForm(template));
                    list.appendChild(card);
                });
            };

            const fetchTemplates = async () => {
                const result = await requestJson('/api/templates?per_page=100');

                if (!result.ok) {
                    logOutput('template-form-output', result);
                    return;
                }

                state.templates = result.data.data ?? [];

                if (state.selectedTemplateId && !state.templates.some((template) => template.id === state.selectedTemplateId)) {
                    state.selectedTemplateId = null;
                }

                renderTemplateSelects();
                renderTemplateList();
                updateTemplateFormActions();
            };

            const state = {
                page: 1,
                perPage: 15,
                templates: [],
                selectedTemplateId: null,
                filters: {
                    status: '',
                    channel: '',
                    priority: '',
                    created_from: '',
                    created_to: '',
                },
            };

            const renderTable = (notifications) => {
                const table = document.getElementById('notification-table');
                table.innerHTML = '';
                const batchIds = new Set();

                notifications.forEach((notification) => {
                    batchIds.add(notification.batch_id);

                    const row = document.createElement('tr');
                    row.className = 'hover:bg-slate-50';
                    row.dataset.notificationId = notification.id;

                    row.innerHTML = `
                        <td class="px-4 py-3 text-slate-900">${notification.to ?? '-'}</td>
                        <td class="px-4 py-3 text-slate-600">${notification.channel ?? '-'}</td>
                        <td class="px-4 py-3"></td>
                        <td class="px-4 py-3 text-xs text-slate-500">${notification.priority ?? '-'}</td>
                        <td class="px-4 py-3 text-xs text-slate-500" data-created-at>${formatDateTime(notification.created_at)}</td>
                        <td class="px-4 py-3 text-xs text-slate-500" data-updated-at>${formatDateTime(notification.updated_at)}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <button class="detail-btn rounded-full border border-slate-900/10 bg-white px-3 py-1 text-xs font-semibold text-slate-700" data-id="${notification.id}">Details</button>
                                <button class="simulate-btn rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700" data-message-id="${notification.provider_message_id ?? notification.id}">Simulate Callback</button>
                                <button class="cancel-btn rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-600" data-id="${notification.id}">Cancel</button>
                            </div>
                        </td>
                    `;

                    row.querySelector('td:nth-child(3)').appendChild(buildStatusBadge(notification.status));
                    table.appendChild(row);
                });

                resetSubscriptions(batchIds);

                table.querySelectorAll('.detail-btn').forEach((button) => {
                    button.addEventListener('click', () => fetchDetail(button.dataset.id));
                });
                table.querySelectorAll('.simulate-btn').forEach((button) => {
                    button.addEventListener('click', () => openCallbackWithMessageId(button.dataset.messageId));
                });
                table.querySelectorAll('.cancel-btn').forEach((button) => {
                    button.addEventListener('click', () => cancelNotification(button.dataset.id));
                });
            };

            const updatePagination = (meta) => {
                const metaEl = document.getElementById('pagination-meta');
                metaEl.textContent = `Page ${meta.current_page} / ${meta.last_page} • ${meta.total} records`;
                document.getElementById('prev-page').disabled = meta.current_page <= 1;
                document.getElementById('next-page').disabled = meta.current_page >= meta.last_page;
            };

            const fetchNotifications = async () => {
                const params = new URLSearchParams({
                    page: state.page,
                    per_page: state.perPage,
                });

                Object.entries(state.filters).forEach(([key, value]) => {
                    if (value) {
                        params.append(key, value);
                    }
                });

                const result = await requestJson(`/api/notifications?${params.toString()}`);

                if (result.ok) {
                    renderTable(result.data.data ?? []);
                    updatePagination(result.data.meta ?? { current_page: 1, last_page: 1, total: 0 });
                }
            };

            const fetchDetail = async (id) => {
                const result = await requestJson(`/api/notifications/${id}`);
                logOutput('details-output', result);
                openDetails();
            };

            const cancelNotification = async (id) => {
                const result = await requestJson(`/api/notifications/${id}/cancel`, 'POST');
                logOutput('details-output', result);
                openDetails();
                await fetchNotifications();
            };

            const openModal = (id) => {
                document.getElementById('modal-backdrop').classList.remove('hidden');
                document.getElementById(id).classList.remove('hidden');
                document.getElementById(id).classList.add('flex');
            };

            const closeModals = () => {
                document.getElementById('modal-backdrop').classList.add('hidden');
                ['create-modal', 'batch-modal', 'templates-modal', 'callback-modal'].forEach((id) => {
                    const modal = document.getElementById(id);
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                });
            };

            const openDetails = () => {
                document.getElementById('details-panel').classList.remove('hidden');
            };

            const closeDetails = () => {
                document.getElementById('details-panel').classList.add('hidden');
            };

            const openCallbackWithMessageId = (messageId) => {
                const form = document.getElementById('provider-callback-form');
                if (form) {
                    form.message_id.value = messageId ?? '';
                }
                openModal('callback-modal');
            };

            document.getElementById('open-create-modal').addEventListener('click', () => openModal('create-modal'));
            document.getElementById('open-batch-modal').addEventListener('click', () => openModal('batch-modal'));
            document.getElementById('open-templates-modal').addEventListener('click', () => {
                openModal('templates-modal');
                fetchTemplates();
            });

            document.querySelectorAll('.close-modal').forEach((button) => {
                button.addEventListener('click', closeModals);
            });
            document.getElementById('modal-backdrop').addEventListener('click', closeModals);
            document.getElementById('close-details').addEventListener('click', closeDetails);

            document.getElementById('filter-form').addEventListener('change', (event) => {
                const form = event.currentTarget;
                state.filters.status = form.status.value;
                state.filters.channel = form.channel.value;
                state.filters.priority = form.priority.value;
                state.filters.created_from = toIsoFromLocal(form.created_from.value) ?? '';
                state.filters.created_to = toIsoFromLocal(form.created_to.value) ?? '';
                state.perPage = form.per_page.value;
                state.page = 1;
                fetchNotifications();
            });

            document.getElementById('reset-filters').addEventListener('click', () => {
                const form = document.getElementById('filter-form');
                form.reset();

                const dateInputs = form.querySelectorAll('[data-datepicker]');
                dateInputs.forEach((input) => {
                    if (input._flatpickr) {
                        input._flatpickr.clear();
                    } else {
                        input.value = '';
                    }
                });

                state.filters = {
                    status: '',
                    channel: '',
                    priority: '',
                    created_from: '',
                    created_to: '',
                };
                state.perPage = form.per_page.value;
                state.page = 1;
                fetchNotifications();
            });

            document.getElementById('refresh-list').addEventListener('click', fetchNotifications);

            document.getElementById('prev-page').addEventListener('click', () => {
                state.page = Math.max(1, state.page - 1);
                fetchNotifications();
            });

            document.getElementById('next-page').addEventListener('click', () => {
                state.page += 1;
                fetchNotifications();
            });

            document.getElementById('create-notification-form').addEventListener('submit', async (event) => {
                event.preventDefault();
                const form = event.currentTarget;
                const variables = collectVariables(document.getElementById('notification-variables'));
                const hasVariables = Object.keys(variables).length > 0;
                const selectedTemplateId = form.template_id.value || undefined;

                const payload = {
                    to: form.to.value,
                    channel: form.channel.value,
                    priority: form.priority.value || undefined,
                    content: form.content.value || undefined,
                    scheduled_at: toIsoFromLocal(form.scheduled_at.value),
                };

                if (selectedTemplateId) {
                    payload.template_id = selectedTemplateId;
                    payload.variables = variables;
                } else if (hasVariables) {
                    payload.variables = variables;
                }

                const headers = {};
                if (form.idempotency_key.value) {
                    headers['Idempotency-Key'] = form.idempotency_key.value;
                }

                const result = await requestJson('/api/notifications', 'POST', payload, headers);
                logOutput('create-notification-output', result);

                if (result.ok) {
                    closeModals();
                    fetchNotifications();
                }
            });

            document.getElementById('batch-notification-form').addEventListener('submit', async (event) => {
                event.preventDefault();
                const form = event.currentTarget;
                const payload = parseJsonInput(form.payload.value, 'batch-notification-output');

                if (payload === undefined) {
                    return;
                }

                const headers = {};
                if (form.idempotency_key.value) {
                    headers['Idempotency-Key'] = form.idempotency_key.value;
                }

                const result = await requestJson('/api/notifications/batch', 'POST', payload ?? { notifications: [] }, headers);
                logOutput('batch-notification-output', result);

                if (result.ok) {
                    closeModals();
                    fetchNotifications();
                }
            });

            document.getElementById('template-form').addEventListener('submit', async (event) => {
                event.preventDefault();
                const form = event.currentTarget;
                const payload = {
                    name: form.name.value,
                    channel: form.channel.value,
                    body: form.body.value,
                };

                const isEditing = Boolean(state.selectedTemplateId);
                const url = isEditing ? `/api/templates/${state.selectedTemplateId}` : '/api/templates';
                const method = isEditing ? 'PATCH' : 'POST';

                const result = await requestJson(url, method, payload);
                logOutput('template-form-output', result);

                if (result.ok) {
                    const template = result.data.data ?? null;
                    if (template) {
                        state.selectedTemplateId = template.id;
                    }
                    await fetchTemplates();
                    if (template) {
                        applyTemplateToForm(template);
                    }
                }
            });

            document.getElementById('template-reset').addEventListener('click', () => {
                resetTemplateForm();
            });

            document.getElementById('template-delete-button').addEventListener('click', async () => {
                if (!state.selectedTemplateId) {
                    return;
                }

                const result = await requestJson(`/api/templates/${state.selectedTemplateId}`, 'DELETE');
                logOutput('template-form-output', result);

                if (result.ok) {
                    state.selectedTemplateId = null;
                    resetTemplateForm();
                    await fetchTemplates();
                }
            });
            document.getElementById('template-preview-button').addEventListener('click', async () => {
                const form = document.getElementById('template-form');
                const previewOutput = document.getElementById('template-preview-output');

                if (!form || !previewOutput) {
                    return;
                }

                if (!form.body.value.trim()) {
                    previewOutput.textContent = 'Template body is required for preview.';
                    togglePreVisibility();
                    return;
                }

                const variables = collectVariables(document.getElementById('template-sample-variables'));
                const hasVariables = Object.keys(variables).length > 0;
                const payload = {
                    body: form.body.value,
                };

                if (hasVariables) {
                    payload.sample_variables = variables;
                }

                const result = await requestJson('/api/templates/validate', 'POST', payload);

                if (result.ok && result.data?.rendered !== undefined) {
                    previewOutput.textContent = result.data.rendered;
                } else {
                    previewOutput.textContent = JSON.stringify(result.data ?? result, null, 2);
                }
                togglePreVisibility();
            });

            document.getElementById('provider-callback-form').addEventListener('submit', async (event) => {
                event.preventDefault();
                const form = event.currentTarget;
                const payload = {
                    message_id: form.message_id.value,
                    status: form.status.value,
                    timestamp: toIsoFromLocal(form.timestamp.value) || new Date().toISOString(),
                };

                if (form.status.value === 'failed') {
                    payload.error = {
                        code: form.error_code.value || 'MANUAL_FAILURE',
                        message: form.error_message.value || 'Simulated failure from UI.',
                    };
                }

                const result = await requestJson(`/api/providers/${form.provider.value}/callbacks`, 'POST', payload);
                logOutput('provider-callback-output', result);

                if (result.ok) {
                    closeModals();
                    fetchNotifications();
                }
            });

            document.getElementById('fetch-metrics').addEventListener('click', async () => {
                const result = await requestJson('/api/metrics');
                logOutput('metrics-output', result);
            });

            document.getElementById('fetch-health').addEventListener('click', async () => {
                const result = await requestJson('/health');
                logOutput('health-output', result);
            });

            document.querySelectorAll('[data-add-variables]').forEach((button) => {
                button.addEventListener('click', () => {
                    const targetId = button.dataset.addVariables;
                    buildVariableRow(document.getElementById(targetId));
                });
            });

            resetVariableList(document.getElementById('notification-variables'));
            resetVariableList(document.getElementById('template-sample-variables'));
            togglePreVisibility();
            fetchTemplates();
            fetchNotifications();
        </script>
    </body>
</html>
