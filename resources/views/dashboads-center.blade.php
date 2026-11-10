<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>مركز لوحات التحكم</title>

    <!-- Cairo Font -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --font-sans-ar: "Cairo", "Segoe UI", sans-serif;

            --primary-500: #99745c;
            --primary-700: #7a5b47;

            --secondary-100: #e3e2dd;
            --secondary-300: #c1bdb5;
            --secondary-800: #1e2b27;

            --grey-200: #e4ded7;
            --grey-500: #7d776f;
            --grey-800: #1e2b27;

            --white: #ffffff;

            --bg-page: #eff3f3;
            --bg-surface: #ffffff;

            --text-primary: #242528;
            --text-secondary: #7e7e7e;

            --border-default: #e2d9cf;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-sans-ar);
            background: var(--bg-page);
            color: var(--text-primary);
        }

        /* Header */
        header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            padding: 24px 40px;
            border-bottom: 1px solid var(--border-default);
            background: var(--bg-surface);

        }

        header img.logo {
            width: 80px;
            height: 80px;
            border-radius: 10px;
        }

        header h1 {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
        }

        header p {
            font-size: 13px;
            color: var(--text-secondary);
        }

        main {
            max-width: 1100px;
            margin: auto;
            padding: 50px 24px;
        }

        .section-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 20px;
        }

        /* Cards */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 20px;
        }

        .card {
            background: var(--bg-surface);
            border: 1px solid var(--grey-200);
            border-radius: 14px;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            transition: 0.2s;
        }

        .card:hover {
            transform: translateY(-4px);
            border-color: var(--primary-500);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        .card-image {
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            background: var(--secondary-100);
        }

        .card-body {
            padding: 16px;
        }

        .card-title {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .card-desc {
            font-size: 13px;
            color: var(--grey-500);
        }

        .card-action {
            margin-top: 12px;
            font-size: 13px;
            color: var(--primary-500);
            font-weight: 600;
        }
    </style>
</head>

<body>

    <header>
        <img class="logo" src="/img/Miran-logo.png" alt="شعار">
        <div>
            <h1>مركز لوحات تحكم الشركة</h1>
            <p>وصول سريع إلى جميع لوحات البيانات</p>
        </div>
    </header>

    <main>
        <div class="section-title">لوحات التحكم</div>

        <div class="grid">

            <a class="card" href="#">
                <div class="card-image">📊</div>
                <div class="card-body">
                    <div class="card-title">إدارة المبيعات</div>
                    <div class="card-desc">الإيرادات والأهداف وخط المبيعات</div>
                    <div class="card-action">فتح</div>
                </div>
            </a>

            <a class="card" href="#">
                <div class="card-image">📈</div>
                <div class="card-body">
                    <div class="card-title">إدارة العمليات</div>
                    <div class="card-desc">مؤشرات الأداء والعمليات</div>
                    <div class="card-action">فتح</div>
                </div>
            </a>

            <a class="card" href="#">
                <div class="card-image">🗂️</div>
                <div class="card-body">
                    <div class="card-title">إدارة التدريب</div>
                    <div class="card-desc">الموظفون والحضور والرواتب</div>
                    <div class="card-action">فتح</div>
                </div>
            </a>

            <a class="card" href="#">
                <div class="card-image">💰</div>
                <div class="card-body">
                    <div class="card-title">إدارة قطاع الأعمال</div>
                    <div class="card-desc">الميزانية والمصروفات</div>
                    <div class="card-action">فتح</div>
                </div>
            </a>

        </div>
    </main>

</body>

</html>
