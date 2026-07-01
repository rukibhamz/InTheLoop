<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — InTheLoop Setup</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f4f6f8;
            --card: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --border: #e5e7eb;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --success: #059669;
            --danger: #dc2626;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
        }

        .wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem 0.75rem;
        }

        @media (min-width: 640px) {
            .wrap {
                padding: 2rem 1rem;
            }
        }

        .card {
            width: 100%;
            max-width: 640px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }

        @media (min-width: 640px) {
            .card {
                padding: 2rem;
            }
        }

        h1 {
            margin: 0 0 0.25rem;
            font-size: 1.75rem;
        }

        .subtitle {
            color: var(--muted);
            margin: 0 0 1.5rem;
        }

        .steps {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .step {
            font-size: 0.875rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: #eef2ff;
            color: #3730a3;
        }

        .step.active {
            background: var(--primary);
            color: #fff;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.35rem;
        }

        input, select {
            width: 100%;
            padding: 0.65rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font: inherit;
            margin-bottom: 1rem;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .grid .field { margin-bottom: 0; }
        .grid input { margin-bottom: 0; }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.7rem 1.1rem;
            border-radius: 8px;
            border: 0;
            background: var(--primary);
            color: #fff;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        .btn:hover { background: var(--primary-hover); }
        .btn.secondary { background: #e5e7eb; color: var(--text); }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }

        .actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }

        .error {
            background: #fef2f2;
            color: var(--danger);
            border: 1px solid #fecaca;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .list {
            list-style: none;
            padding: 0;
            margin: 0 0 1.5rem;
        }

        .list li {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border);
        }

        .badge {
            font-size: 0.8125rem;
            font-weight: 600;
        }

        .badge.ok { color: var(--success); }
        .badge.fail { color: var(--danger); }

        .help { color: var(--muted); font-size: 0.875rem; margin-top: -0.5rem; margin-bottom: 1rem; }

        @media (max-width: 640px) {
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            @yield('content')
        </div>
    </div>
    <script>
        document.querySelectorAll('[data-driver]').forEach(function (select) {
            var toggle = function () {
                var sqlite = select.value === 'sqlite';
                document.querySelectorAll('[data-sqlite-hide]').forEach(function (el) {
                    el.style.display = sqlite ? 'none' : '';
                });
            };
            select.addEventListener('change', toggle);
            toggle();
        });
    </script>
</body>
</html>
