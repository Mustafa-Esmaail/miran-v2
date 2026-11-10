<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Miran OPS Performance</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        @@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Tajawal:wght@300;400;500;700&display=swap');

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        :root {
            --bg: #0D1117;
            --sidebar: #111827;
            --surface: #1C2333;
            --surface2: #232D3F;
            --surface3: #2A3550;
            --border: #2E3A50;
            --border2: #3A4A65;
            --text: #E8EDF5;
            --text2: #8B9BB4;
            --text3: #4A5A75;
            --cyan: #00D4AA;
            --cyan2: #00B894;
            --cdim: rgba(0, 212, 170, 0.12);
            --gold: #F0A500;
            --gdim: rgba(240, 165, 0, 0.12);
            --blue: #4F8EF7;
            --bdim: rgba(79, 142, 247, 0.12);
            --purple: #A855F7;
            --pdim: rgba(168, 85, 247, 0.12);
            --red: #FF6B6B;
            --rdim: rgba(255, 107, 107, 0.12);
            --green: #4ADE80;
            --orange: #FB923C;
            --odim: rgba(251, 146, 60, 0.12);
            --r: 10px;
            --rl: 14px;
        }

        ::-webkit-scrollbar {
            width: 5px;
            height: 5px
        }

        ::-webkit-scrollbar-track {
            background: transparent
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border2);
            border-radius: 3px
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background: var(--bg);
            color: var(--text);
            font-size: 14px;
            line-height: 1.6;
            min-height: 100vh;
            overflow-x: hidden;
            direction: rtl
        }

        body.ltr {
            font-family: 'Inter', sans-serif;
            direction: ltr
        }

        body.ltr .sidebar {
            border-right: 1px solid var(--border);
            border-left: none
        }

        body.ltr .shell {
            flex-direction: row
        }

        body.ltr .nav-item {
            flex-direction: row;
            text-align: left
        }

        body.ltr .kpi-actions {
            left: auto;
            right: 8px
        }

        body.ltr .topbar {
            flex-direction: row
        }

        body.ltr .topbar-right {
            flex-direction: row
        }

        body.ltr .topbar-left {
            flex-direction: row
        }

        body.ltr .tbl-wrap table th,
        body.ltr .tbl-wrap table td {
            text-align: left
        }

        body.ltr .mth-btn {
            flex-direction: row
        }

        body.ltr .mth-item {
            flex-direction: row
        }

        body.ltr .page-hdr {
            flex-direction: row
        }

        body.ltr .page-hdr-left {
            flex-direction: row
        }

        body.ltr .page-hdr-right {
            flex-direction: row
        }

        body.ltr .sidebar-footer {
            text-align: left
        }

        body.ltr .nav-count {
            margin-left: auto;
            margin-right: 0
        }

        body.ltr .modal {
            direction: ltr;
            text-align: left
        }

        body.ltr .modal-actions {
            flex-direction: row
        }

        body.ltr input,
        body.ltr select,
        body.ltr textarea {
            text-align: left
        }

        body.ltr .chart-legend {
            justify-content: flex-start
        }

        body.ltr .sec-hdr {
            flex-direction: row
        }

        body.ltr .sec-actions {
            flex-direction: row
        }

        body.ltr .kpi-card-top {
            flex-direction: row
        }

        body.ltr .kpi-label,
        body.ltr .kpi-value,
        body.ltr .kpi-vs {
            text-align: left
        }

        body.ltr .toast {
            right: 20px;
            left: auto
        }

        .shell {
            display: flex;
            min-height: calc(100vh - 52px);
            flex-direction: row-reverse
        }

        /* TOPBAR */
        .topbar {
            background: var(--sidebar);
            border-bottom: 1px solid var(--border);
            height: 52px;
            display: flex;
            flex-direction: row-reverse;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            position: sticky;
            top: 0;
            z-index: 200
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-direction: row-reverse
        }

        .logo-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 40px;
            flex-shrink: 0
        }

        .logo-wrap img {
            height: 36px;
            width: auto;
            object-fit: contain
        }

        .brand-name {
            font-size: 15px;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -.3px
        }

        .brand-sub {
            font-size: 10px;
            color: var(--text2)
        }

        .live-pill {
            display: flex;
            align-items: center;
            gap: 5px;
            background: var(--cdim);
            border: 1px solid rgba(0, 212, 170, 0.25);
            border-radius: 20px;
            padding: 3px 10px;
            font-size: 11px;
            color: var(--cyan);
            font-weight: 500;
            flex-shrink: 0
        }

        .live-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--cyan);
            animation: blink 2s infinite
        }

        @keyframes blink {

            0%,
            100% {
                opacity: 1
            }

            50% {
                opacity: .3
            }
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-direction: row-reverse
        }

        .lang-toggle {
            display: flex;
            align-items: center;
            background: var(--surface2);
            border: 1px solid var(--border2);
            border-radius: 8px;
            overflow: hidden
        }

        .lang-btn {
            padding: 5px 11px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            background: transparent;
            color: var(--text2);
            transition: all .15s;
            font-family: inherit
        }

        .lang-btn.active {
            background: var(--cyan);
            color: #0D1117
        }

        .lang-btn:hover:not(.active) {
            color: var(--text)
        }

        .lang-sep {
            width: 1px;
            height: 18px;
            background: var(--border2)
        }

        /* BUTTONS */
        .btn {
            border: none;
            border-radius: 7px;
            padding: 6px 12px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all .15s;
            display: inline-flex;
            align-items: center;
            gap: 5px
        }

        .btn-ghost {
            background: var(--surface2);
            color: var(--text2);
            border: 1px solid var(--border)
        }

        .btn-ghost:hover {
            background: var(--surface3);
            color: var(--text)
        }

        .btn-cyan {
            background: var(--cyan);
            color: #0D1117
        }

        .btn-cyan:hover {
            background: var(--cyan2)
        }

        .btn-danger {
            background: rgba(255, 107, 107, 0.15);
            color: var(--red);
            border: 1px solid rgba(255, 107, 107, 0.25)
        }

        .btn-danger:hover {
            background: var(--red);
            color: #fff
        }

        .btn-gold {
            background: var(--gold);
            color: #0D1117
        }

        .btn-gold:hover {
            background: #D4920A
        }

        .btn-purple {
            background: var(--purple);
            color: #fff
        }

        .btn-purple:hover {
            background: #9333EA
        }

        .btn-xs {
            padding: 3px 7px;
            font-size: 10px;
            border-radius: 5px
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 11px
        }

        /* SIDEBAR */
        .sidebar {
            width: 228px;
            flex-shrink: 0;
            background: var(--sidebar);
            border-left: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            position: sticky;
            top: 52px;
            height: calc(100vh - 52px)
        }

        .nav-section {
            padding: 14px 10px 6px
        }

        .nav-label {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--text3);
            padding: 0 8px 8px;
            text-transform: uppercase
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 9px 10px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            color: var(--text2);
            transition: all .15s;
            margin-bottom: 2px;
            flex-direction: row-reverse
        }

        .nav-item:hover {
            background: var(--surface);
            color: var(--text)
        }

        .nav-item.active {
            background: var(--cdim);
            color: var(--cyan);
            border: 1px solid rgba(0, 212, 170, 0.18)
        }

        .nav-icon {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
            background: var(--surface2)
        }

        .nav-item.active .nav-icon {
            background: rgba(0, 212, 170, 0.15)
        }

        .nav-item.admin-nav.active {
            background: rgba(168, 85, 247, 0.12);
            color: var(--purple);
            border-color: rgba(168, 85, 247, 0.2)
        }

        .nav-item.admin-nav.active .nav-icon {
            background: rgba(168, 85, 247, 0.15)
        }

        .nav-count {
            margin-right: auto;
            font-size: 10px;
            background: var(--surface2);
            padding: 1px 6px;
            border-radius: 8px;
            color: var(--text3)
        }

        .nav-item.active .nav-count {
            background: rgba(0, 212, 170, 0.2);
            color: var(--cyan)
        }

        .sidebar-div {
            height: 1px;
            background: var(--border);
            margin: 8px 12px
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 12px 16px;
            border-top: 1px solid var(--border);
            font-size: 10px;
            color: var(--text3);
            text-align: right
        }

        /* MONTH DROPDOWN */
        .mth-section {
            padding: 10px
        }

        .mth-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-direction: row-reverse;
            padding: 9px 12px;
            background: var(--surface);
            border: 1px solid var(--border2);
            border-radius: 9px;
            cursor: pointer;
            color: var(--text);
            font-size: 13px;
            font-weight: 500;
            font-family: inherit;
            transition: all .15s
        }

        .mth-btn:hover {
            border-color: var(--cyan)
        }

        .mth-btn.open {
            border-color: var(--cyan);
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0
        }

        .chevron {
            font-size: 10px;
            color: var(--text3);
            transition: transform .2s
        }

        .mth-btn.open .chevron {
            transform: rotate(180deg)
        }

        .mth-panel {
            max-height: 0;
            overflow: hidden;
            transition: max-height .3s ease;
            background: var(--surface);
            border: 1px solid var(--cyan);
            border-top: none;
            border-radius: 0 0 9px 9px
        }

        .mth-panel.open {
            max-height: 360px;
            overflow-y: auto
        }

        .mth-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-direction: row-reverse;
            padding: 7px 14px;
            cursor: pointer;
            font-size: 12px;
            color: var(--text2);
            transition: all .12s
        }

        .mth-item:hover {
            background: var(--surface2);
            color: var(--text)
        }

        .mth-item.active {
            color: var(--cyan);
            font-weight: 600
        }

        .mdot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            flex-shrink: 0
        }

        .mdot.has {
            background: var(--cyan)
        }

        .mdot.empty {
            background: var(--border2)
        }

        /* MAIN */
        .main {
            flex: 1;
            overflow: auto;
            padding: 20px 24px
        }

        .page-hdr {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
            flex-direction: row-reverse
        }

        .page-hdr-left {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-direction: row-reverse
        }

        .page-title {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -.3px
        }

        .month-chip {
            background: var(--cdim);
            border: 1px solid rgba(0, 212, 170, 0.3);
            color: var(--cyan);
            border-radius: 6px;
            padding: 3px 10px;
            font-size: 12px;
            font-weight: 600
        }

        .page-hdr-right {
            display: flex;
            gap: 8px;
            flex-direction: row-reverse
        }

        .sec-hdr {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            flex-direction: row-reverse
        }

        .sec-title {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--text2)
        }

        .sec-actions {
            display: flex;
            gap: 6px;
            flex-direction: row-reverse
        }

        /* PANEL CARDS */
        .panels-row {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 12px;
            margin-bottom: 20px
        }

        .panel-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--rl);
            padding: 16px;
            cursor: pointer;
            transition: all .2s;
            position: relative;
            overflow: hidden
        }

        .panel-card:hover {
            border-color: var(--border2);
            background: var(--surface2);
            transform: translateY(-1px)
        }

        .panel-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px
        }

        .c-cyan::before {
            background: var(--cyan)
        }

        .c-gold::before {
            background: var(--gold)
        }

        .c-blue::before {
            background: var(--blue)
        }

        .c-purple::before {
            background: var(--purple)
        }

        .c-orange::before {
            background: var(--orange)
        }

        /* GAUGES */
        .gauges-sec {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--rl);
            padding: 18px;
            margin-bottom: 20px
        }

        .gauges-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(155px, 1fr));
            gap: 16px
        }

        .gauge-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px
        }

        .gauge-val {
            font-size: 21px;
            font-weight: 700;
            line-height: 1;
            margin-top: 2px
        }

        .gauge-lbl {
            font-size: 11px;
            color: var(--text2);
            font-weight: 500;
            text-align: center
        }

        .gauge-status {
            font-size: 10px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 4px
        }

        .gs-on {
            background: rgba(74, 222, 128, 0.15);
            color: #4ADE80
        }

        .gs-monitor {
            background: rgba(240, 165, 0, 0.15);
            color: #F0A500
        }

        .gs-risk {
            background: rgba(255, 107, 107, 0.15);
            color: #FF6B6B
        }

        /* KPI CARDS */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(185px, 1fr));
            gap: 10px;
            margin-bottom: 20px
        }

        .kpi-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--r);
            padding: 14px 15px;
            position: relative;
            transition: all .15s
        }

        .kpi-card:hover {
            border-color: var(--border2);
            background: var(--surface2)
        }

        .kpi-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 8px;
            flex-direction: row-reverse
        }

        .kpi-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            flex-shrink: 0
        }

        .kpi-badge {
            font-size: 10px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 5px
        }

        .kb-up {
            background: rgba(74, 222, 128, 0.15);
            color: #4ADE80
        }

        .kb-down {
            background: rgba(255, 107, 107, 0.15);
            color: #FF6B6B
        }

        .kb-flat {
            background: var(--surface3);
            color: var(--text3)
        }

        .kpi-label {
            font-size: 11px;
            color: var(--text2);
            margin-bottom: 4px;
            font-weight: 500;
            text-align: right
        }

        .kpi-value {
            font-size: 22px;
            font-weight: 700;
            line-height: 1.1;
            letter-spacing: -.5px;
            text-align: right
        }

        .kpi-vs {
            font-size: 10px;
            color: var(--text3);
            margin-top: 3px;
            text-align: right
        }

        .kpi-prog {
            height: 3px;
            background: var(--surface3);
            border-radius: 2px;
            margin-top: 10px;
            overflow: hidden
        }

        .kpi-prog-bar {
            height: 100%;
            border-radius: 2px;
            transition: width .5s
        }

        .kpi-actions {
            position: absolute;
            top: 8px;
            left: 8px;
            display: none;
            gap: 4px;
            z-index: 2
        }

        .kpi-card:hover .kpi-actions {
            display: flex
        }

        .kpi-card.dragging {
            opacity: .4;
            border: 1px dashed var(--cyan)
        }

        /* CHARTS */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px
        }

        .chart-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--rl);
            padding: 16px
        }

        .chart-card.full {
            grid-column: 1/-1
        }

        .chart-wrap {
            position: relative;
            width: 100%
        }

        .chart-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 8px;
            justify-content: flex-end
        }

        .leg-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            color: var(--text2)
        }

        .leg-dot {
            width: 8px;
            height: 8px;
            border-radius: 2px;
            flex-shrink: 0
        }

        /* TABLE */
        .tbl-wrap {
            border: 1px solid var(--border);
            border-radius: var(--r);
            overflow: hidden;
            margin-bottom: 20px
        }

        .data-tbl {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px
        }

        .data-tbl th {
            background: var(--surface2);
            padding: 8px 12px;
            text-align: right;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .5px;
            color: var(--text3);
            border-bottom: 1px solid var(--border);
            text-transform: uppercase
        }

        .data-tbl td {
            padding: 8px 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            color: var(--text2);
            text-align: right
        }

        .data-tbl tr:last-child td {
            border-bottom: none
        }

        .data-tbl tr:hover td {
            background: rgba(255, 255, 255, 0.02);
            color: var(--text)
        }

        /* FORECAST */
        .forecast-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--rl);
            padding: 18px;
            margin-bottom: 0
        }

        .forecast-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            margin-bottom: 8px
        }

        .fb-up {
            background: rgba(74, 222, 128, 0.15);
            color: #4ADE80
        }

        .fb-down {
            background: rgba(255, 107, 107, 0.15);
            color: #FF6B6B
        }

        .fb-stable {
            background: rgba(79, 142, 247, 0.15);
            color: #4F8EF7
        }

        /* REPORT */
        .report-section {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--rl);
            padding: 20px;
            margin-bottom: 16px
        }

        /* ADMIN */
        .admin-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--rl);
            padding: 18px;
            margin-bottom: 14px
        }

        .admin-card.highlighted {
            border-color: rgba(168, 85, 247, 0.35)
        }

        .admin-section-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--purple);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-direction: row-reverse
        }

        /* UPLOAD LOG */
        .upload-log-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            font-size: 12px;
            flex-direction: row-reverse
        }

        .upload-log-item:last-child {
            border-bottom: none
        }

        .date-badge {
            font-size: 10px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 8px;
            background: var(--surface3);
            color: var(--text2)
        }

        .grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--r);
            padding: 14px 16px
        }

        .stat-val {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -.5px
        }

        .stat-lbl {
            font-size: 11px;
            color: var(--text2);
            margin-top: 2px
        }

        .trend-arrow {
            font-size: 11px;
            font-weight: 600;
            margin-top: 4px
        }

        .ta-up {
            color: #4ADE80
        }

        .ta-down {
            color: #FF6B6B
        }

        .tag {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 600
        }

        .tag-purple {
            background: var(--pdim);
            color: var(--purple)
        }

        /* MODALS */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.65);
            z-index: 300;
            align-items: center;
            justify-content: center
        }

        .modal-overlay.open {
            display: flex
        }

        .modal {
            background: var(--surface);
            border: 1px solid var(--border2);
            border-radius: var(--rl);
            padding: 24px;
            width: 520px;
            max-width: 95vw;
            max-height: 88vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            direction: rtl;
            text-align: right
        }

        body.ltr .modal {
            direction: ltr;
            text-align: left
        }

        .modal-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 18px
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px
        }

        .fg {
            display: flex;
            flex-direction: column;
            gap: 5px
        }

        .fg.full {
            grid-column: 1/-1
        }

        label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text2)
        }

        input,
        select,
        textarea {
            padding: 8px 11px;
            border: 1px solid var(--border2);
            border-radius: 7px;
            font-family: inherit;
            font-size: 13px;
            background: var(--surface2);
            color: var(--text);
            outline: none;
            transition: border .15s;
            width: 100%;
            text-align: right
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--cyan)
        }

        body.ltr input,
        body.ltr select,
        body.ltr textarea {
            text-align: left
        }

        select option {
            background: var(--surface2)
        }

        .modal-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            margin-top: 18px;
            flex-direction: row-reverse
        }

        /* IMPORT */
        .import-zone {
            border: 2px dashed var(--border2);
            border-radius: var(--r);
            padding: 28px;
            text-align: center;
            cursor: pointer;
            transition: all .15s;
            margin-bottom: 14px
        }

        .import-zone:hover,
        .import-zone.drag-over {
            border-color: var(--cyan);
            background: var(--cdim)
        }

        /* TOAST */
        .toast {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background: var(--cyan);
            color: #0D1117;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            z-index: 999;
            transform: translateY(60px);
            opacity: 0;
            transition: all .25s;
            box-shadow: 0 4px 20px rgba(0, 212, 170, 0.3)
        }

        .toast.show {
            transform: translateY(0);
            opacity: 1
        }

        body.ltr .toast {
            right: 20px;
            left: auto
        }

        @media print {

            .topbar,
            .sidebar,
            .btn,
            .kpi-actions,
            .no-print {
                display: none !important
            }

            .main {
                padding: 8px
            }
        }
    </style>
</head>

<body>
    <!-- TOPBAR -->
    <div class="topbar">
        <div class="topbar-left">
            <div class="logo-wrap"><img id="logoImg" alt="Miran Logo"></div>
            <div style="text-align:right">
                <div class="brand-name">Miran OPS Performance</div>
                <div class="brand-sub" id="brandSub">لوحة متابعة الأداء التشغيلي</div>
            </div>
            <div class="live-pill">
                <div class="live-dot"></div><span id="liveLbl">مباشر</span>&nbsp;—&nbsp;<span id="liveDate"></span>
            </div>
        </div>
        <div class="topbar-right">
            <span style="font-size:11px;color:var(--text3)" id="lastUpdTxt"></span>
            <div class="lang-toggle">
                <button class="lang-btn" id="btnEn" onclick="setLang('en')">EN</button>
                <div class="lang-sep"></div>
                <button class="lang-btn active" id="btnAr" onclick="setLang('ar')">عربي</button>
            </div>
            <button class="btn btn-ghost" onclick="downloadTemplate()">⬇ <span id="tTpl">قالب</span></button>
            <button class="btn btn-ghost" onclick="openImportModal()">⬆ <span id="tImp">استيراد</span></button>
            <button class="btn btn-cyan" onclick="exportCurrentMonth()">⬇ <span id="tExp">تصدير</span></button>
        </div>
    </div>

    <div class="shell">
        <div class="sidebar" id="sidebarEl"></div>
        <div class="main" id="mainArea"></div>
    </div>

    <!-- KPI MODAL -->
    <div class="modal-overlay" id="kpiModal">
        <div class="modal">
            <div class="modal-title" id="kpiModalTitle">إضافة مؤشر</div>
            <div class="form-grid">
                <div class="fg full"><label id="lKpiName">اسم المؤشر</label><input type="text" id="kpiName"
                        placeholder="مثال: إجمالي الإيرادات"></div>
                <div class="fg"><label id="lKpiTeam">الفريق</label>
                    <select id="kpiTeam">
                        <option value="sales" id="oSales">المبيعات</option>
                        <option value="cs" id="oCS">خدمة العملاء</option>
                        <option value="coord" id="oCoord">المنسقون</option>
                        <option value="training" id="oTraining">التدريب</option>
                    </select>
                </div>
                <div class="fg"><label id="lKpiUnit">الوحدة</label>
                    <select id="kpiUnit">
                        <option value="number" id="oNumber">رقم</option>
                        <option value="currency" id="oCurrency">ريال سعودي</option>
                        <option value="percent" id="oPercent">نسبة مئوية %</option>
                        <option value="days" id="oDays">أيام</option>
                        <option value="hours" id="oHours">ساعات</option>
                        <option value="text" id="oText">نص / تقييم</option>
                    </select>
                </div>
                <div class="fg"><label id="lKpiTarget">القيمة المستهدفة</label><input type="number" id="kpiTarget"
                        placeholder="100"></div>
                <div class="fg"><label id="lKpiHB">الأعلى أفضل؟</label>
                    <select id="kpiHigherBetter">
                        <option value="yes" id="oYes">نعم</option>
                        <option value="no" id="oNo">لا</option>
                    </select>
                </div>
                <div class="fg"><label id="lKpiIcon">الأيقونة (إيموجي)</label><input type="text" id="kpiIcon"
                        placeholder="💰" maxlength="2"></div>
            </div>
            <div class="modal-actions">
                <button class="btn btn-cyan" id="btnSaveKpi" onclick="saveKpi()">حفظ</button>
                <button class="btn btn-ghost" id="btnCancelKpi" onclick="closeKpiModal()">إلغاء</button>
            </div>
        </div>
    </div>

    <!-- IMPORT MODAL -->
    <div class="modal-overlay" id="importModal">
        <div class="modal">
            <div class="modal-title" id="importModalTitle">استيراد بيانات الشهر</div>
            <div class="fg" style="margin-bottom:14px"><label id="lSelMonth">اختر الشهر</label><select
                    id="importMonth" style="width:100%"></select></div>
            <div class="import-zone" id="importZone" onclick="document.getElementById('fileInput').click()"
                ondrop="handleDrop(event)" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                <div style="font-size:28px;margin-bottom:8px">📊</div>
                <p><strong id="iClick" style="color:var(--text)">انقر للاختيار</strong> <span id="iDrag">أو
                        اسحب وأفلت</span></p>
                <p style="margin-top:4px;font-size:11px;color:var(--text3)" id="iFormat">يدعم ملفات .xlsx و .xls</p>
            </div>
            <input type="file" id="fileInput" accept=".xlsx,.xls" style="display:none"
                onchange="handleFileSelect(event)">
            <div id="importPreview" style="display:none;max-height:180px;overflow-y:auto">
                <div class="tbl-wrap">
                    <table class="data-tbl" id="previewTable"></table>
                </div>
            </div>
            <p style="font-size:11px;color:var(--text3);margin-top:10px" id="iTip">💡 قم بتنزيل القالب أولاً
                للحصول على هيكل مؤشرات الأداء.</p>
            <div class="modal-actions">
                <button class="btn btn-cyan" id="confirmImportBtn" onclick="confirmImport()"
                    style="display:none">استيراد البيانات</button>
                <button class="btn btn-ghost" id="btnCancelImport" onclick="closeImportModal()">إلغاء</button>
            </div>
        </div>
    </div>
    <div class="toast" id="toast"></div>

    <script>
        // ═══════════════════ LOGO ═══════════════════
        (function() {
            var img = document.getElementById('logoImg');
            if (img) {
                var b64 =
                    '/9j/4AAQSkZJRgABAQAAAQABAAD/4gHYSUNDX1BST0ZJTEUAAQEAAAHIAAAAAAQwAABtbnRyUkdCIFhZWiAH4AABAAEAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAAAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlkZXNjAAAA8AAAACRyWFlaAAABFAAAABRnWFlaAAABKAAAABRiWFlaAAABPAAAABR3dHB0AAABUAAAABRyVFJDAAABZAAAAChnVFJDAAABZAAAAChiVFJDAAABZAAAAChjcHJ0AAABjAAAADxtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAHMAUgBHAEJYWVogAAAAAAAAb6IAADj1AAADkFhZWiAAAAAAAABimQAAt4UAABjaWFlaIAAAAAAAACSgAAAPhAAAts9YWVogAAAAAAAA9tYAAQAAAADTLXBhcmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABtbHVjAAAAAAAAAAEAAAAMZW5VUwAAACAAAAAcAEcAbwBvAGcAbABlACAASQBuAGMALgAgADIAMAAxADb/2wBDAAUDBAQEAwUEBAQFBQUGBwwIBwcHBw8LCwkMEQ8SEhEPERETFhwXExQaFRERGCEYGh0dHx8fExciJCIeJBweHx7/2wBDAQUFBQcGBw4ICA4eFBEUHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh7/wAARCAIAAgADASIAAhEBAxEB/8QAHQABAAMAAwEBAQAAAAAAAAAAAAcICQMFBgQCAf/EAFYQAAEDAgMCBwkMBQgKAgMAAAABAgMEBQYHEQghEjFBcYGU0RMXIjdRYXORsRQVFjI1VVZ0daGysyM0NkLBMzhSYoKDksIkQ0ZTcnaToqPDRNIYJWP/xAAaAQEAAgMBAAAAAAAAAAAAAAAAAwUBAgQG/8QAJhEBAAICAQMDBAMAAAAAAAAAAAECAxEEBSExEhNRIjJBYXGhsf/aAAwDAQACEQMRAD8AuWAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABQnaexliarzivVC68VcVLbplp6aGGVWNYxEReJF3qq8al9jOnaQ8eGKvrzvYgHkUv+IF4rzcusv7R7/4g+ebl1l/aXn2abBYqrJTDtRU2a3zTPgcrnyU7XOcvCXjVUJH+DGG/mC19UZ2AZo+/wDiD55uXWX9o9/8QfPNy6y/tNLvgxhv5gtfVGdg+DGG/mC19UZ2AZo+/wDiD55uXWX9o9/8QfPNy6y/tNLvgxhv5gtfVGdg+DGG/mC19UZ2AZo+/wDiD55uXWX9o9/8QfPNy6y/tNLvgxhv5gtfVGdg+DGG/mC19UZ2AZo+/wDiD55uXWX9o9/8QfPNy6y/tNLvgxhv5gtfVGdg+DGG/mC19UZ2AZo+/wDiD55uXWX9o9/8QfPNy6y/tNLvgxhv5gtfVGdg+DGG/mC19UZ2AZo+/wDiD55uXWX9o9/8QfPNy6y/tNLvgxhv5gtfVGdg+DGG/mC19UZ2AZo+/wDiD55uXWX9o9/8QfPNy6y/tNLvgxhv5gtfVGdg+DGG/mC19UZ2AZo+/wDiD55uXWX9o9/8QfPNy6y/tNLvgxhv5gtfVGdg+DGG/mC19UZ2AZo+/wDiD55uXWX9o9/8QfPNy6y/tNLvgxhv5gtfVGdg+DGG/mC19UZ2AZo+/wDiD55uXWX9o9/8QfPNy6y/tNLvgxhv5gtfVGdg+DGG/mC19UZ2AZo+/wDiD55uXWX9p22Dsc4ssOJaG6W++1zZ4pW6JJM57XIq6K1WquioqGgGNsNYeZgy9vZYra1zbdUK1yUrEVFSN2/iM3aL5Qh9K32gamUr1kpopHcbmIq9KHIcND+owejb7DmAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAGdO0h48MVfXnexDRYzp2kPHhir6872IBcvZg8RuGvq6/iUksjTZg8RuGvq6/iUksAAAAAAAAAAAAAAAAAAAAAAAAAAAOmxz+xN9+zaj8txmRRfKEPpW+003xz+xN9+zaj8txmRRfKEPpW+0DUqh/UYPRt9hzHDQ/qMHo2+w5gAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABnTtIePDFX153sQ0WM6dpDx4Yq+vO9iAXL2YPEbhr6uv4lJLI02YPEbhr6uv4lJLAAAAAAOtxRfLdhvD9bfLtOkNFRxLLK7l0TkTyqvEiHisrc58E5hTOo7VVy0lwTipKxEZI9PK3RVR3QpH23EmJ5sEW+C10M0tmSZZbjNFv4Kp8RHIn7u9V18xTWjqaiiq4quknkgnhej45I3K1zXIuqKipxKBqkeCz1zDgy2wLLe1hbUVksiQUcLl0R8ioq6r5kRFVT5NnPHjsf5a0lyq5EfcqVfctavlkaieF0oqKQ/tRxVeP8AO3C2XFE9yRxMSSZU4mLIurndDGgRDZs+MxaLGzcS1N7nrEV/6Wie7SnczXexGcSeZeMvhg2+U+JsKWvEFLG6OG4UrKhjHcbUcmui8xEWa+znhfEdgo4sMU9NZLnRsZG2VjNGTsTRF4aJxu038Lj14yY8N2qnseH7fZqVqNgoqaOCNE8jWon8AOwAAAAAAAAPC545gQ5cYDqL8sLaise5IKOFy6I+VeLXzIiKqnujx+ZWXWH8wW26HEaVEtLQyulbBHJwGvcqaeEqbwKVSZ/ZoSYgZdnYjla1siO9ytY1IVTX4vB04i+OEbu2/wCF7ZemM4CVtLHPwf6KuaiqnrKubVuU+CcK4ctFbhegWguNVWtpWQMkc5JkVPIqrvTd6yz2BrW6yYNs9oemj6SiiienkcjU1+/UBjn9ib79m1H5bjMii+UIfSt9ppvjn9ib79m1H5bjMii+UIfSt9oGpVD+owejb7DmOGh/UYPRt9hzAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADOnaQ8eGKvrzvYhosZ07SHjwxV9ed7EAuXsweI3DX1dfxKSWRpsweI3DX1dfxKSWAAAAAAfieKKeF8M8bJYnorXsemqOReRUIzv2ROW1wst2oaXDlLQzXDw0qIkXhQyIi8FWar4KarvRNykngCnuyZcq7Amct6y9vLu5rVcKLg6+D3eJVVrk/4m6/ce8yQpPhPtGY/xrMxXw26VbdSOdv0drwXKnQxf8R4fbEtdbg7NGxZg2Nfc81S1FWRE3JPF5edqpz6KS9skWuWlynZeatEWsvdZNXTP5X8J2iL9ygTAAAAAAAAAAABw19XTUFFPW1kzIKeBiySyPXRGtRNVVTmIjx9ZsS5m4pfhfWa14IoZES41DV4MlxkTesTP6iLuVeLVF49APL4Op6nOfN5mPKqJ7MH4eesVojkTT3TOi75NPJrovQieUsIfHZLXb7Jaqe1WqkipKKmYjIoo00a1EPsA6bHP7E337NqPy3GZFF8oQ+lb7TTfHP7E337NqPy3GZFF8oQ+lb7QNSqH9Rg9G32HMcND+owejb7DmAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAGdO0h48MVfXnexDRYzp2kPHhir6872IBcvZg8RuGvq6/iUksjTZg8RuGvq6/iUksAAdZiq80uHsPVt5rF/RU0Sv05XO4mtTzquidIHZg+SyuqX2mkkrNPdD4mvlRORypqqfefWAAAEV7UeDZ8Z5U1lPQUr6m40MjaqlYxurnKm5yInLq1VPc4BtCWDBFlsqMRi0dDFC9E/pIxOF9+p3YAAAAAAAAA4a2rpaKBZ6yphp4kVEV8r0a3VeLepzIqKmqLqilNdrXHFTizMmhwBa6hUoaCpZFKjF3SVL1RN/l4OunPqXFoo1io4Il42RtavQgHKAAAAA6bHP7E337NqPy3GZFF8oQ+lb7TTfHP7E337NqPy3GZFF8oQ+lb7QNSqH9Rg9G32HMcND+owejb7DmAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAGdO0h48MVfXnexDRYzp2kPHhir6872IBcvZg8RuGvq6/iUksjTZg8RuGvq6/iUksAQ3nTdvf3MzBeWtM/hNqKxtyuLUXf3KHVzWrzq1V6EJkK05GVbsa7TmNcXOcklPb4nUlLrv0arkY1U50Y5f7QFlgAAAAAAAAHKjWq5yoiImqqvIdThfEdnxNQzV1krGVdNDUPpnSNTdw2Lo5E8vOB2wAAHTY5v0GGMIXXEFTp3OhpXzaKunCcieCnSuiHclfNuPEy2vLuhw/C/gzXaq1eiL/qo01X71agFYsrVnxHnXY5qx6yT1t4ZPK5eVyv4ar6zSAzt2aIFqM8cLtRNeBV8NeZGqpokAAAAAAdNjn9ib79m1H5bjMii+UIfSt9ppvjn9ib79m1H5bjMii+UIfSt9oGpVD+owejb7DmOGh/UYPRt9hzAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADOnaQ8eGKvrzvYhosZ07SHjwxV9ed7EAuXsweI3DX1dfxKSWRpsweI3DX1dfxKSWB1uK6p9Dhe61kS6PgoppGr5FaxVT2FdNgx0ctuxXO5UWofVRq5eXRUVfbqWDx4x0mB76xiaudbqhET+7cUP2fc1JMrcS1dVPQvrrfWxJHUwMejXIrV1a5FXlTVfWBfjE18t+HbNNdbnL3OniVrd3G5zlRGtROVVVUQ7GN3CY1ytVqqiLovGhU+wZgXfPfOaw2yChfb8NWib3wmp1fwlf3PiV68S6roiJyaqWxAAAAAAIb2tcerg7LWSgoZ1jut4VaaBWro5kf8ArH+rdzqcOxfTuhyRppHLr3eunkTm1Rv+UrTtVYvfivNy4xxSq+itTloqdNd2rV8NU53a+otRsjs4GQthXTThOnd/5ngSwAABRjbSxF78ZuLbI38KG0UrKfRF3I9fCd070ToLyyORkbnrxNRVUzGzBvD7/ji9Xl71f7rrZZGqvHwVcvB+7QD3uyNEkue1k1TXgsmd6o3F/ihWxu3hZ62zi3U1Qv8A41L6gAAAAAHTY5/Ym+/ZtR+W4zIovlCH0rfaab45/Ym+/ZtR+W4zIovlCH0rfaBqVQ/qMHo2+w5jhof1GD0bfYcwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAzp2kPHhir6872IaLGeG03SVNJnhiX3TBJF3aq7rHwk04bHImjk8qKBcTZg8RuGvq6/iUksjTZg8RuGvq6/iUksD8ysZLG6ORqOY9Fa5q8SovGhTDMvZjxnDiesqMIxUlwtU8yvgY6obHJEirrwXI7RF04tUUuiAIo2bsqW5a4We64pFJfq7R1XIxdUjanxY2ryonKvlJXAAAAAdJjy9sw5gy8XyRyNSipJJUVf6SN8H79DuyGtse7OtmSVdDG9Wvr6mKmTTlRV4Tk9TVAojVzyVVXNUyuV0kr1e5V5VVdVL/wCycipkJhzXlbOv/neZ9mhGym3TIPDPnjmX/wA8gEoAAD5rrutdWv8A/F/4VMspf5V3Opqbdfkur9A/8KmWUv8AKO51AmPY08etu+q1H5al8yhexs7TPW2+emqE/wDGpfQAAAAAA6bHP7E337NqPy3GZFF8oQ+lb7TTfHP7E337NqPy3FF9mrLSTMPHaOrI3pZbaqTVkibuEuvgxovlVU9SKBfuh/UYPRt9h+55oqeJ008rIo2pq573I1E51Ufo4Id6tZHG3jVdEREKS7U2cs+L7xNhbD1UrcP0cnBkkjXT3XInGuv9BF4k5eMC7NPPBUxpLTzRzMXicxyORelDkMzMH47xdhGtZVWC/VtG5i/ESRXRuTyKxdyp0Fusg9oS240fDYcUdwtl+do2J6LwYapfNr8V3m5eQCeQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAPE504+o8usC1V+nRktUv6Kip1dp3WVeJOZONfMgHttU1013oQZtd5ax4twW/EtugV14s0av8ABTfNBxub59N7k6T49je5YixLbcUYsv8AXT1T6+vayNXuVWorW6uRqciJwmpuJ9kY2SN0b2o5rkVHIvKigRtsweI3DX1dfxKSWdNgvD9LhfD8Nlol/wBGge9Yk0+K1zlcidGuh3IAAAAAAAAAr7t2qqZU2tE4lvEev/SlLBFfNu5FXKq1r5LxH+VKBSo0N2XGKzIXCyKmmsEi+uaRTPI0c2eYPc+SWEo9NNbbG/8Axau/iB7wAAfNdfkur9A/8KmWUv8AKO51NTLwvBtFY7yU71/7VMsnrq9y+cCW9kOTuee1m/rRzt9cbi/hn1spKqZ64f0XTwpPwKaCgAAAAAHW4qpZq7C91oqdvCmqKKaKNuvG5zFRE9anncmcB0WXuBqSx06NdUqnda2ZP9ZMqeEvMnEnmQ9oeHzux7T5dYCq769GSVbv0NFC5f5SVybuhN6rzARHtiZs+9FvdgPD9UiV9Uz/APYyxu3wxrxRp5HO5fInOU7PrvFxrLvdaq6XCd89XVSulmkcuqucq6qp8gA/cUkkMrJYnuZIxUc1zV0VFTiVD8AC9WyjmrLjvDMtlvc7XXy1tajnqu+oh4kfzpxL0KTcZx5A4jqMMZt4fr4Hqkc1WylnTXRHRyORi682uvQaOAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACiO1rjWbGGaclkoZHSUFof7kgY1dUkm18NydPg9BbvOzFzMEZaXi/8LSeOHuVMmu9ZX+C37116CmGzHhp+Ms6bc+sb3ano3uuFWrk14XBXVEXnerfvAudkjhRuC8sLLYlaiTxwd1qV8sr/AAnfeunQe0AAAAAeexviemw1b0lc1JqmRdIoddNfOvmPvxJeaSxWuSuq3bm7mMTje7kRCAcQXarvV0lr6t6q56+C3XcxOREK3qHN9ivpr90/07eHxfen1W8QmnB2M7diFiRapTVqJ4ULncf/AArynpysMUj4pGyRPcx7V1a5q6Kikn4GzFT9Hb785ddeCyq/+3aQcPqkX+jL2n5TcngTX6sfj4SeD8xvZJG2SNyPY5NWuRdUVD9FyrAgfbih7pk9BJp/JXSF3ra9P4k8EMbZ0aPyMrnrxx1dO5P+oifxAocaYZS0rqLK/C9I9NHRWqmaqedI2ma1vp31dfT0kaavmlbG3nVUT+JqNZYPctnoqbTTuVOxmnM1EA+sAAfHfVRtkr3LxJTSKv8AhUy0d8Zec1IxH+z1y+qS/gUy3d8ZecCU9lFquz1sGicTpF/7HGghQrY5g7tnrbHaapHT1D1/6a9pfUAAAAIuzdzwwfl5w6KeV1zvCJ4NDTOTVq/13cTfvXzFUsxdoDMHF0skUFxdZKB25KegcrF087/jL9wF48Q4twxh5qre7/bqBUTVWzVDWu0/4ddfuKp7YeOMIY2orMmHMTxVslvkk7pTMjdwXcNG+GjtNNU4OnSVynmmnkWSeWSV7l1Vz3Kqr0qcYAAAAD0eXeDb3jrE1PYbHTrJNKuskip4ELOV7l5EQDvcgMGXTGWZdpp6GJ3uejqY6qrmVPBjjY5HLqvlXTROc0YPG5Q5eWbLjCsdmtbEknfo+rqlbo+eTTjXzJyJyHsgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAfxzka1XOVERE1VV5AKmbd2LllrrRgunlXgQt921TUXjcurWIvMnCXpPSbCuF0osH3TFU0aJLcKjuELlTf3OPj6OEv3FZ85sSOxZmffr3w1fFNVuZBryRM8Fn3InrL65G2ZLBlHhm2dz7m9lBHJIn9d6cN33uUD2YAAHFW1MFHSy1VTIkcMTVc9y8iHKcVXTw1dNJTVMbZYZGq17HJuVDE712ZjW+6BMcYkqMRXV0qq5tJEqpBH5E8q+dTz57jHuBaizvfXW1rp6BV1Vqb3Rc/lTznhzx3Kplrkn3fL0mC2O1I9vwHa4YslXfrrHQ0rdyrrI9eJjeVVPittFUXCuio6SNZJpXcFrUJ7wVhynw7aW07Ea6pf4U8um9y+TmQn4PDnkX3P2x5Rcvkxhr28y7Kz0ENrtkFBT8JY4WI1FcuqqfWAeqiIrGoUEzMzuQhLbVq46fJKeB66Oqq6CNnnVHcL2NUm0q1t739iUOHMMxyeG6SStmb5EROAz2vMsK+5KWn37zXw3beCrmyV8bnacjWrwlX7jScpBsRWF1yzZlu7mKsNqonv4XIkkngNT1K9egu+AAAHV4uXg4Uu666aUM66/3bjLxeNTT3Hz0jwLf5F3o22VKr0ROMwl4wLBbCdF3fNK51it1SltT1RfIrpGJ7NS6pVrYEtTmUGJ725qcGWSGlYvL4KOcv4k9RaUD8TyxU8D555GxxRtVz3uXRGonGqlT8/wDaR90xz4cy9nkjYurKi6acFy8ipFyp/wAXq8pJG2Pi52HMqZLZSzrFW3mVKZvBXRUiTfIvSmjf7RRIDknmlqJnzTyvllevCc97lVzl8qqpxnJTwT1MrYaeGSaR3ExjVcq9CHsLNlTmNd42yUGDrvJG/wCK91OrGr0u0A8WCWaPZ1zbqdFXDbIEXllrIm6dHC1O9o9lfMyZEWaax06cqOq3KqepigQSCz1n2Rbs9zHXfFtJE395tPTucvQqqnsJHwhsv5e2eVk91dW3yRu/g1EnAiXna3TX1gVOysy1xPmJeWUVlo3Npkd/pFbIipDC3lVV5V8yby+OUuXNhy4w2y1WiJH1D9HVVW9v6Sd/lVeRPInIens9rttmoI6C00NPQ0saaMhgjRjW9CH2AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADx2dl9+DmVWIrs16MkjonsjVV08NycFOneexIO22bitHku6lb/864QwrzJq/wDyAUfoo1qa+CJd6yytbz6qajWWPuNnooUTTgU8bfU1DLm3ze56+nn/AN3K1/qVFNRrNKk9oopkXVJKdjvW1FA+sAAAAB/HIjmq1yIqLuVF5SPMbZdQ1ivrrGjYZ1XV0Crox3N5FJEBDn4+PPX03hLizXxW3WXjst8ItsNH7srY2rcZU8Ll7mn9FD2IBthxVw0ilfDXJktktNrAAJGj+Pe1jHPe5Gtamqqq7kQzmz+xkuOM0Lrd4pOHRxye56PydyZuRenevSWk2vczG4TwguGbXUIl4u7FY/gr4UMHE53mVeJOkpVY7bVXm80dqoo1kqauZsMbU5XOXQC5mxDhlbVltVX+aLgzXepVWKqb1iZ4KdGvCJ/OowZZKfDeE7XYaRNIaClZAnnVE3rzquq9J24AAAefzLdwcucTO8loqvyXGY6mmWbD+55X4pf5LPVflOM6cA4fqMVYytWH6ZHK+tqWRKqfut18JehNVAvNso4ddh/Je091jVk9w4VY/VNF0evg/wDaieslY4LbSQW+3U1BTMRkFNE2KNqcTWtRERPUh8eK7xT4fw1cb3VORsNFTPmdrxeCmqJ6wKVbZuK1vuaz7NDLwqWyxJBoi7u6uThP9WqJ0KfZs8ZAOx5bIcUYhrX0tkfI5sMMC/pajgrou/8Adbqip5dxCd9uNVfb/W3SpVZKmuqXzPXlVz3Kv8TR3KCxJhvLLD1m4KNdT0MfdNE43uThOX1qoHJg3L/B2EKZsGH7BRUit45eBwpXedXrqq+s9OAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAK5bekyty+sMHI+6K5eiJ3aWNK57ekKuy9sVRyR3Tgr/aid2AUzNGNnfETMTZO4fr0kR88NMlLUb9VSSPwV150RF6TOcsJsZ5kRYcxPLg+6zpHb7s9Fp5Hu0bHUcSJ5kcm7nRALqAAAAAAAAAAAeIzjzIsuW+GJLncZGy1kjVbRUaO0fO/wDg1OVTps7M58OZb0T6dz2XG+Pb+hoY37268TpF/dT71KL4/wAYX3HGIpr5f6t09RJuY1NzImcjGpyIgHz4zxJdcW4krL/eZ1mrKp6ucvI1ORrU5ERNyE2bEuCVvOOKjFlZCq0dnZpAqpufUO3J/hbqvOqECWyiqrlcae30ML56mpkbFFG1NVc5V0RDR/JzBVNgHAFuw/CjXTsYklVIifykzt7l5tdyeZAPYAAAAAPH52OkblHipYmuc73rnTROPRWLr9xXrYYwO6WuuOOq6FUjhT3JQK5u5zl/lHpzJonSvkLZVEMVRBJTzxslikarHsemrXNVNFRU5UVDgtVuoLVQRUFso4KOliTSOGFiNa3mRAPqIB228W+8+XEGHKeVG1V5nRHoi70hZ4TvWvBT1k/KqIiqq6InGpnntK42XG2adwqYJeHb6Fy0lHou5WsXRXJzrqvNoB0+R+HlxTmth+z8FVjkq2yS6ckbPDd9zVNJGojWo1E0RE0RCn+whhlajEt5xXMz9HRwJSQKv9N66u+5E9alwAABwVtZSUUKzVdTFBGn7z3IiGJmIjcsxG/DnB4a95l2WjVY6GOWvkTlb4LPWvYeJveYl/uCKyB7KGNeSFPC9anDm6lgx9t7n9OrHwst/wAa/lMtwuVBb4llrqyCnb/Xeia8ycp466ZlW5kyU1opJrhO5eCzTwWqvtX1EQSSVNbUIskktRM9dEVyq5yqS9ltgptpZHdbk1HVzm6sjXiiRf4nJj5uflX9OKNR+Z8ui/FxcevqyTufh7O1PrJbfDJXxxxVLm6yMZro1fJvPpALmI1GlbM7kABlgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACFNtC1rcMlKipa1VdQVkNQmicmqsX7nqTWdDmHYWYnwPebC9EX3bSSRN1/pKng/foBmKfpj3Rva9jla5q6o5F0VFP3WU8tJVzUs7VbLC90b2ryKi6KcQFtdnraMo30dJhfHs6w1EaJHT3R66senIkvkX+ty8pZ+mngqqdlRTTRzQyJwmSRuRzXJ5UVOMyrPX4HzLxvgtyJh/EFXTwIuq0z3cOFf7DtUTo0UDSkFM7JtZ4upomsulgtVc5OORiuiVehFVDu3bXtT3PwcGQ8Pz1i6fhAtiCml22tMXzxOZbsP2mjcvE9yvkVOhVI6xVnhmdiJHx1WJ6mkgfxxUWkDfW3wvvAvLjrMXBuCqZ8uIL5S08jU1Sna7hzO8yMTeViza2orveYprZgikktFI5FatbKqLUPT+qibmfepXOonmqJXTVEsksjl1c97lcq9KnGBy1lTUVlTJVVc8tRPI7hPkkernOXyqq71OIEubN+UdZmNiJtdXMdFh6hkRaqVd3dncfcm8/KvIgEn7F+VruH3w75TojURWWuJ7ePkdL/AATpUtacVFTU9FSQ0lJCyCnhYjI42N0axqJoiInkOUAAAAAAAHW4nvlsw3Yau93epbT0VJGskj3ebkTyqvEiARjtWZhtwRl5JRUcul2vCOpqdGrvjZp4cnQi6c6lCFVVXVd6qexzhx5cMw8bVd9rHPZArljo4FXVIYkXwU5+VfOcuRmEX41zPs9k7nwqdZe7VS6bkiZ4TtefTTpAutsy4V+CmT1mppY+BV1sfu2p1TejpN6IvM3gp6ySz8xRsiiZFG1GsY1GtROJETiQ/QHQ43xFDh20OqF0fUSeDBHrxu8q+ZCB7lcK25VLqitqZJ5HLrq9yrp5k8hImJMKYnxRiWaoqGspKNju5wrI/XRicqInl4zvLDlvZKHgyVvDr5U/p7mepCj5OHkczJqI1WPn/VrgyYeNTczu0/CIbZa7hc5UioKOaodrp4DdUTnU9zY8ra6bSS7VcdMz/dxeE718SfeSrBBS0VPwIIoqeFicTWo1qIRdj7P7L7Cs/uKO4OvNwV/A9z0Oj0auunhP+Kn3r5ibD0jFTvedosvUclu1Oz3eH8KWSyaOo6NrpkT+Vk8J3r5Og7w/EL0lhZIiaI9qO05z9lnSlaRqsahw2va07tOwAG7UAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAFDtrrA0mFczqi7U8HBtt7VamJyJ4LZf9Y317+khg0jznwBQZi4IqbFVcGOpT9LRz6b4pU4l5l4l8ymeOLMP3bC9/q7HeqSSlraV6sexycfkci8qLxooHVAAAAAAAAA/qIqqiIiqq8SITxkPs9XnF8tPe8VRT2uw6o9sbk4M1UnmRfitXyr0AeSyLyjveZV6Y5sb6WxwPT3XWuTdpysZ5XL9xffCmHrRhaw01ksdHHSUVM3gsY1OPyqq8qryqc2HrLa8P2iC02aihoqKnbwY4om6InavnPvAA+agroK1Z/c7ke2GVYnOTiVycadC7j6QAAAAHmcwseYYwJaH3HEVzip04KrFAi6yzL5GN419gHe3Ovo7Zb57hcKmKmpadiySyyO4LWNTjVVKLbSucs+Yd2S02aSWHDlI9e5tXwVqX/7xyeTyIfBnlnZiDMerdRRK+22Bjv0VGx2+T+tIv7y+biQigAXF2HcDrbcOVuNa2DSe5L3CkVyb0havhKnO5P+0q3lzhWuxpjK3Ydt7HLJVSoj3omvc40+M5fMiamlGHbTR2KxUNmt8SRUlFA2GJqcjWpoB94AAAADp8cKqYKvqoqoqW6o0VPRuMx6P5Qh9K32mm+Of2Jvv2bUfluMyKL5Qh9K32galUP6jB6NvsOY4aH9Rg9G32HMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACPM5cpcN5l2zg18fuS6QtVKaviTw2eZyfvN83qJDAGdWZ2T2NsBVUi3C1y1dvavgV9KxXxKnn03tXzKR4u5dFNV3sa9ise1HNcmitVNUVCP8V5MZa4lldNccL0jJnrq6Wm1hcv+HQDOgF4a3ZXy1nkV0M97pU/ox1TVT/uapz27ZdywpXItRHdq3zS1en4UQCjDWuc5GtRXKvEiISLl3kvj/G0sb6GzTUVC7etbWNWKPTyprvd0IXgwrlbgDDDmvs+F7fFK1NElfH3R/rdrvPZIiImibkAhfKTZ3whgt0Fyujff28M0cks7f0MTv6jP4rr0E0IiIiIiaInEgAA8xmniqDBmBLpf5VaskEKtp2Kvx5XbmN9aoenK97S9fJiDM/AWW0C8KKprWV1cxF42I/ci9DXr6gJiy1tk9pwLaaSrc59WtO2Wpc7jdK/w3qv9pynogiaIiJyAAfBfr1abDbpLjerjTUFJGmrpZ5Ea1PXxr5iKdofO2LLVI7TbbctdeqiLujO6oqQxNXcjnL+8vmQphjvG+J8b3R1wxHdJqt6qqsj10jj8zWpuQCyGbW1NDD3a2ZfUqTSb2++VSzwU87GLx87vUVfxNiC9Ylusl0vtxqK+rk45JXa6eZPInmQ65kUr10ZG93M1VO1tWFsTXZ2lsw9da30FHI9PWiAdOCXcJ7OuZ19exZrQy0wKqayVsiNVE8vBTVSfsrdmTC+GqqG54kq3X6ujVHNiVnAp2OT+rxu6d3mA4NjbLR+G8OSYxu1O5lyusaNp2PbosVPx6+ZXLovMiFhD+Na1rUa1Ea1E0RE4kQ/oAAAAAB02Of2Jvv2bUfluMyKL5Qh9K32mm+Of2Jvv2bUfluMyKL5Qh9K32galUP6jB6NvsOY4aH9Rg9G32HMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAI5mzqwBR4vuGFrrdltlwoZ1hetSxWxvXdvR3Fy8pIxnTtIePDFX153sQDQ+grKS4UkdZQ1UNVTSpwo5YXo9jk8qKm5TnI02YPEbhr6uv4lJLAAAAAABWHD8/v7twXKSoRFS3U744kXk4EaIi/epZ4pLc8Z0+A9r28X6uR/uJK58NSrE1Vsb2oiqicum5QLtH4glinibLDI2SN3xXNXVF6Svucefdmq7RHhfLuuW53u7ubTRzwoqMp0eumuq/vb92nFxk64atzbRh222piq5KOljg1XjXgtRNfuA5LpabXdY2x3O20dcxu9raiBsiJzcJFOsbgrBzV1TCtkRfqEf/wBTvwB19FY7LRKi0Vot9MqcXcqZjPYh2CIicSIgAAAAAAAAAAAAdNjn9ib79m1H5bjMii+UIfSt9ppvjn9ib79m1H5bjMii+UIfSt9oGpVD+owejb7DmOGh/UYPRt9hzAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADOnaQ8eGKvrzvYhosZ07SHjwxV9ed7EAuXsweI3DX1dfxKSWQhs649wXasnLBQXLFFppKuGFWyQzVLWvavCXjRVJB75uXn0zsfXGdoHrgeR75uXn0zsfXGdo75uXn0zsfXGdoHrgeR75uXn0zsfXGdo75uXn0zsfXGdoHrimG1plTiODH1Zi2z2ypuFsuSJLK6BivdDLpo5HIm/RdNUUtD3zcvPpnY+uM7QuZuXi8eM7H1xnaBW/Y+yluMuI0xviO3y0tLQ6pQxTsVrpZf6ei79Gp9/MW+PIpmbl4iaJjOx9cZ2jvm5efTOx9cZ2geuB5Hvm5efTOx9cZ2jvm5efTOx9cZ2geuB5Hvm5efTOx9cZ2jvm5efTOx9cZ2geuB5Hvm5efTOx9cZ2jvm5efTOx9cZ2geuB5Hvm5efTOx9cZ2jvm5efTOx9cZ2geuB5Hvm5efTOx9cZ2jvm5efTOx9cZ2geuB5Hvm5efTOx9cZ2jvm5efTOx9cZ2gdrjn9ib79m1H5bjMii+UIfSt9pobjLMjAM+ELzBDjCyySyUE7GMbVsVXOWNyIiJrx6meNF8oQelb7QNS6H9Rg9G32HMcND+owejb7DmAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAFINovKnH1Vm1ebrbMNXC50Vwm90QzUcLpWoip8V2nEqacSl3wBm53p8zPoJiDqL+wd6fMz6CYg6i/sNIwBm53p8zPoJiDqL+wd6fMz6CYg6i/sNIwBm53p8zPoJiDqL+wd6fMz6CYg6i/sNIwBm53p8zPoJiDqL+wd6fMz6CYg6i/sNIwBm53p8zPoJiDqL+wd6fMz6CYg6i/sNIwBm53p8zPoJiDqL+wd6fMz6CYg6i/sNIwBm53p8zPoJiDqL+wd6fMz6CYg6i/sNIwBm53p8zPoJiDqL+wd6fMz6CYg6i/sNIwBm53p8zPoJiDqL+wd6fMz6CYg6i/sNIwBm53p8zPoJiDqL+wd6fMz6CYg6i/sNIwBm53p8zPoJiDqL+wd6fMz6CYg6i/sNIwBm53p8zPoJiDqL+w7PC2TGZdyxBRUbsI3Sia+VvCnq6d0UUaIuqq5yoaIADjpmLHTxxquqsYjV6EOQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAFI9oDNbMGizfvlos+I62hpKSoSnggp1RE0RE38W9VUu4Z8Z3oi7R96ReJbwz2tAlrZTzlxJdcaPwfjG5yV/utjlpJp0RHxyt3qzVONFRF6ULWlDc/7FUZX57x3e0osMMs0dzo1TcnxvCb/iRU5lLwYWvNJiHDdvvlC7hU9dTsmZv4uEmunOi7ugDsj8yvZFG6WRyNYxFc5yruRE41P0RRtVYy+CGUtclPLwK+6L7iptF0VOEnhu6G69KoBWnNbPTHV5xvdJMNX6st9nppXMpoqfRE7m1eDw3Lpxqu/pJl2Mcc4oxbSX+mxHdprl7lfE6F82iubwtdU18m4hjB2CGU2zNjDHFbAnd6x8NPROcm9I2zs4bk513dBIewH/tT/cf5gLWLxKUpwBmNjms2kKa11WJ7jNQy3mWnfTOk/RrGjnIjeDxciF1XfFXmM/8ALT+dHRf8wTfjeBoCVZ2ycwcYYXxjaLXh2+VNspn0KzSNgVEV71eqaqunkQtMUw28fGPZvsz/ANjgOutK7TF2tlNc7fVYknpKqNJYZWyM0e1U1RUOC6Y42hsBSR1l+qbzDTov/wA2BskK+ZV0/iW4yV8UeE/smn/LQ9JeLbRXe11FsuNPHU0tTGscsb2oqORU05QIc2ec96XMSf3gvVLHQX9jFezua/oqlE41ai70cnGqE3GdeBGPw3tB2qmt8jv9Ev7YGK1eNndeAqdLVVDRQARBtX49rMD5cIlpq3Ut1uU6QU8jFThMaiavcnRu186EvlJ9rS91GNs66HCFscsraBWUUbUXcs8jk4S/e1OgDzWVWc2NbPj201N6xLcq22PqGsq4aiZXNWNy6Ku/i01118xfyNzXsa9i6tciKi+VCl+2Bl1R4SpcLXS00zIqX3I23TqxumskbUVrl87k19SlidmzFiYuyis9ZLLw6ukj9x1Kquqq+Pcirzt0X1gSQRxtKYhu2F8nrxd7JVupK5ixMjmaicJnCkaiqmvLoqkjkR7X3iHvXpaf81oFUMNZ65l2i9U1dPiasr4IpEWWmqFRzJW8rV3ewvpgzENBirC9BiC2PR1NWwpI3fqrV5Wr50XVCiWWuXy4zyfxdc6KPhXSyzw1ELUTVZI+A7ht9SapzEm7EGYKUtbVYAuU69zqVWotyuXc1+nhsTnTfzovlAtuVE2u8wcaYczQittixFXW6kZRRyJFA/gpwl11VfKW7KO7b3jjZ9nQ/wAQLj4BrKm44GsNfWSrLU1Nup5ZXrxue6NqqvrU7s85lf4tcM/ZNL+U09GAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAM+M7f5yF5+2GfiaaDmfGdv8AOQvP2wz8TQLF7a+DvfzLqDEdNFwqqyS8J6px9xfojvUqNU+HYdxit1wXXYTqpVdUWmRJIEcu9YH+Tmdr60J9v1spb1Y620VsaSU1ZA+CVq8rXIqL7SiOUN1q8p8/22+4vWKGOrfbq3XcixudojubXguAv2Un2rsQ1WO85aTBlpc6aK3yNo42t3o6oeqcNejcnQpbTMvFFNg/Al1xHO9ulLTq6JF/fkXcxOlVQqjsc4bqMV5rV+NLq108du4c6yP38OplVdF50RXLz6ATHnzh+mwrsp1uHqRqJFQUtLDu/eVJo+E7nVdV6TwWwH/tT/cf5iV9rPxB4i5oPz2EUbAf+1P9x/mAtW74q8xn/lp/Ojov+YJvxvNAHfFXmM/8tP50dF/zBN+N4GgJTDbx8Y9m+zP/AGOLnlMNvHxj2b7M/wDY4CV8rs88sLPlzh61XHEqQVlJboYZ41pZV4L2sRFTVG6LvPhzL2oMIUNkqKfBr6m63SRithldA6OGJV/eXhaKqp5ETpOpy/2aMDYgwFZL1V3C8sq6+giqJO5ys4LXPaironB4t/lImztydvWUtxpL9b6pLjaFnb3GpfEirFIm9GyNXVN+m7kUD0eyvlffsSY8pce32llhtdHOtUySZvBWqn11bwU5URd6rxbtC6RGGzjmTBmLgZksscNPdaBUhrIIm8FvF4L2pyNVOTkVFJPA6rGF7psOYWud9rHI2Ghpnzu38fBTcnOq6J0lOdkuyVONc66zFdzb3ZKDh10r14lmkVUan3qvQSrtw4uW1YFocL08vBqLvMrpURd/cY9FX1uVp0+yLiHL/BmXc814xXZ6K63KpWSeKapa17GN8FjVRelekCWNo/CaYvykvFDHEj6qmj9103lR8e/dzpqnSQBsLYrWhxbdMIVMqpFcIfdFO1V/1sfxkTzq1df7JY52bWWDmq12OLArVTRUWrbvQpDV3agwLntJeMNXCGsttDdO7U81O/hMfC5dVaipx+C5W9AGipEe194h716Wn/NaSpbKyC4W6mr6V6PgqYmyxuRdUVrk1RfvIr2vvEPevS0/5rQI62BWtfY8WMeiOa6enRUXiVOC8inP7CdblRnDFdLHwqaknmS4W2RnExUdq5nQvJ5FQlfYD+RsV/WKf8LyUtpPL9mPsuaqCnhR11oEWpoV03q5E3s/tJu59APU5ZYuoccYKt2I6BycGpjTurNd8cibnNXmUqFtveONn2dD/E7XYsx+6w4sqME3SZY6O6O4VMj107nUpu083CRNOdEOq23vHGz7Oh/iBb/K/wAWuGfsml/KaejPOZX+LXDP2TS/lNPRgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADPjO3+chefthn4mmg5QPOq23B+0jdkZQVTu6XeNzNInLwkVW6Km7eBfxCmu3JhFLZjG3YtpIlbDdI1incibkmZxL0t0/wAKlyiOdo3B3w0ypulvhiWStpm+66RGpq5ZGJronOmqdIFXM6M4FxllJhLDcNQ51ZHGj7vuXwpI04DE15dd7l8+hZrZjwcmD8pbZDNCsdfcG+7KrVNFRz97WrzN06dSnWRGA63F+aFrtVTQ1DKOKXu9Y50SojY2b1RdU3aqiJ0mibGtYxrGIiNamiInIgEV7WfiDxFzQfnsIo2A/wDan+4/zEt7VcE1RkRiKKnhklfwYV4LGq5dEmYqronmIq2CqWphZieSanmjY5YURz2KiKvheUC0rvirzGf+Wn86Oi/5gm/G80Ad8VeYoTltb69m1FRq+hqWo2/TOdrE5NE4T967uIC+5TDbx8Y9m+zP/Y4ueU3266OrlzCsssVLPJGtt4KObGqpr3R27XygWayY8UmE/sim/LQ+rM7C9NjLAd2w7UsRyVdO5I1X92RN7HJzORDgydjkhypwrFLG6ORtpp0c1yaKi9zTcqHqwKFbMWJqnAmdUNpuLu4U9dK63VjXbka/XRirzPRE5lUvqUb2usGVmHM2n362UsyUl1RtWySNiqjJ0XR6apy6ojukspQY9rZNnNcbvpJ/fBlqcqxLG7hLMnga6cenC36+TeBVzaIvVTmNn460W1/dY4Z47VRom9NeFo5f8TnKS43ZFw9wU4WLrlrpv0pmdpHex3hGsv2a0mJLlTTrBa2OqFkkYqI6d+qN3rxrvcpdsCtP/wCIuHfpdc+rM7SKtorI+HLGzW27226VNypamd0EzpYkb3J2mreJeXR3qL1nhs+cKpjLKu9WZkfDqO493pkRNV7qzwm6c+mnSB5TZAxYmI8oqSgmlR1XZnLRvRV39zTfGv8AhXT+yfRtfeIe9elp/wA1pAuxXd7lZM1J7DUUtSymulM9j0dGqIySNFcir5NyOTpQn7a3hmnyKvTIIpJX90gXgsaqrokrd+4CN9gP5GxX9Yp/wvLQFZNgmlqYLHih89PLE19RAjXPYqI7Rr9dNePjLNgUa2qsET5f5nQYmsjX09FcpfddPIzckNQ1dXNTyb9HJznkc+saU+PcSWzEEOjZn2uGOqYn7kzdUeiebXenOXczuwJT5hZf1tjcjW1aJ3aikX9yZqeD0LxLzmet0wziC2XaW1Vtmr4a2OTuboVgdwldrpu3b/NpxgaPZX+LXDP2TS/lNPRnR5fUs9DgPD9FVRuingttPHKx3G1zY2oqL0neAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABomuum8AAAACIicSAAAERE4kAADRNddN4AAKiLxoAAAABUReNAAARETiTQAAAAA0TXXRAAARETiAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAB/9k=';
                img.src = 'data:image/png;base64,' + b64;
            }
        })();

        // ═══════════════════ CONSTANTS ═══════════════════
        var MN_AR = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر',
            'ديسمبر'
        ];
        var MN_EN = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October',
            'November', 'December'
        ];

        // ═══════════════════ TRANSLATIONS ═══════════════════
        var TRANS = {
            ar: {
                brandSub: 'لوحة متابعة الأداء التشغيلي',
                liveLbl: 'مباشر',
                lastUpd: 'آخر تحديث:',
                tplBtn: 'قالب',
                impBtn: 'استيراد',
                expBtn: 'تصدير',
                navNav: 'التنقل',
                navPeriod: 'الفترة الزمنية',
                navTools: 'الأدوات',
                navAdmin2: 'مدير العمليات',
                navOverview: 'لوحة التحكم',
                navSales: 'المبيعات',
                navCS: 'خدمة العملاء',
                navCoord: 'المنسقون',
                navTraining: 'التدريب',
                navTrends: 'الاتجاهات',
                navForecast: 'توقعات الأداء',
                navReports: 'التقارير',
                navImport: 'استيراد / تصدير البيانات',
                navTemplate: 'تنزيل القالب',
                navPrint: 'طباعة التقرير',
                navAdminPanel: 'لوحة الإدارة',
                sidebarFtr: 'لوحة العمليات v3.0',
                addKpiTitle: 'إضافة مؤشر أداء',
                editKpiTitle: 'تعديل المؤشر',
                lKpiName: 'اسم المؤشر',
                lKpiTeam: 'الفريق',
                lKpiUnit: 'الوحدة',
                lKpiTarget: 'القيمة المستهدفة',
                lKpiHB: 'الأعلى أفضل؟',
                lKpiIcon: 'الأيقونة (إيموجي)',
                oSales: 'المبيعات',
                oCS: 'خدمة العملاء',
                oCoord: 'المنسقون',
                oTraining: 'التدريب',
                oNumber: 'رقم',
                oCurrency: 'ريال سعودي',
                oPercent: 'نسبة مئوية %',
                oDays: 'أيام',
                oHours: 'ساعات',
                oText: 'نص / تقييم',
                oYes: 'نعم',
                oNo: 'لا',
                btnSaveKpi: 'حفظ المؤشر',
                btnCancel: 'إلغاء',
                importTitle: 'استيراد بيانات الشهر',
                lSelMonth: 'اختر الشهر',
                iClick: 'انقر للاختيار',
                iDrag: 'أو اسحب وأفلت',
                iFormat: 'يدعم ملفات .xlsx و .xls',
                iTip: '💡 قم بتنزيل القالب أولاً للحصول على هيكل مؤشرات الأداء.',
                btnImport: 'استيراد البيانات',
                pgOverview: 'الأداء التشغيلي',
                pgOverviewSub: 'نظرة عامة',
                kpiComplete: 'مؤشر مكتمل',
                gCsat: 'معدل الرضا',
                gRetention: 'الاحتفاظ بالعملاء',
                gTrainingComp: 'إكمال التدريب',
                gOnTime: 'التسليم في الوقت',
                gConversion: 'معدل التحويل',
                gFCR: 'الحل من أول تواصل',
                gsOn: 'في الهدف',
                gsMon: 'مراقبة',
                gsRisk: 'خطر',
                gsNoData: 'لا توجد بيانات',
                chMix: 'مزيج القنوات',
                chMixSub: 'توزيع نوع التواصل',
                chTrend: 'اتجاه حجم التواصل',
                chTrendSub: 'مكالمات · دردشات · شكاوى',
                chRev: 'الإيرادات — صافي مقابل الضريبة',
                chRevSub: 'ر.س · الأشهر الستة الأخيرة',
                chTeam: 'أداء الفرق',
                chTeamSub: 'نسبة تحقيق الهدف',
                legCalls: 'مكالمات',
                legChats: 'دردشات',
                legComplaints: 'شكاوى',
                legSurveys: 'استبيانات',
                legNetRev: 'صافي الإيرادات',
                legTax: 'ضريبة القيمة المضافة',
                viewAll: 'عرض الكل ←',
                noKpis: 'لا توجد مؤشرات.',
                pgKpi: 'مؤشرات الأداء',
                pgEnterData: '✏ إدخال البيانات',
                pgAddKpi: '+ مؤشر',
                pgRating: 'تقييم الأداء الشهري',
                pgNotes: 'ملاحظات مدير الأداء لهذا الشهر...',
                pgSaveNote: 'حفظ الملاحظة',
                noteSaved: 'تم حفظ الملاحظة',
                pgTable: 'جدول البيانات',
                colKpi: 'المؤشر',
                colActual: 'القيمة الفعلية',
                colTarget: 'الهدف',
                colPct: '% من الهدف',
                colStatus: 'الحالة',
                colDate: 'التاريخ',
                pgVsTarget: 'الأداء مقابل الهدف',
                pgDist: 'توزيع الأداء',
                noKpisEmpty: 'لا توجد مؤشرات',
                addFirst: 'أضف مؤشرك الأول.',
                btnAddKpi: '+ إضافة مؤشر',
                pctOf: '% من الهدف',
                pgTrends: 'الاتجاهات',
                allMonths: 'جميع الأشهر',
                notEnough: 'بيانات غير كافية',
                notEnoughSub: 'استورد بيانات لشهرين على الأقل.',
                pgForecast: '🔮 توقعات الأداء',
                next3: '3 أشهر قادمة',
                fcRefresh: '🔄 تحديث',
                fcInfoTitle: 'ℹ️ حول التوقعات',
                fcInfoText: 'تعتمد التوقعات على الانحدار الخطي والمتوسط المتحرك للبيانات التاريخية المتاحة. كلما زادت البيانات المُدخلة، زادت دقة التوقعات.',
                fcLinear: 'انحدار خطي',
                fcMA: 'متوسط متحرك',
                fcGrowth: 'معدل النمو:',
                fcConf: 'ثقة:',
                fcBasedOn: 'بناءً على',
                fcMonths: 'شهر من البيانات',
                fcNext3: 'توقع الأشهر الثلاثة القادمة —',
                fcUp: 'تصاعدي',
                fcDown: 'تنازلي',
                fcStable: 'مستقر',
                fcHigh: 'عالية',
                fcMed: 'متوسطة',
                fcLow: 'منخفضة',
                fcActual: 'بيانات فعلية',
                fcLinLbl: 'توقع خطي',
                fcMALbl: 'متوسط متحرك',
                pgReports: '📋 التقارير',
                rptSettings: '⚙️ إعدادات التقرير',
                rptType: 'نوع التقرير',
                rptRefMonth: 'الشهر المرجعي',
                rptTeams: 'الأقسام المُدرجة',
                rptMonthly: 'تقرير شهري',
                rptWeekly: 'تقرير أسبوعي',
                rptQuarterly: 'تقرير ربع سنوي',
                btnPDF: '🖨 تصدير PDF',
                btnExcel: '⬇ Excel',
                rptExec: 'ملخص تنفيذي',
                rptTotalRev: 'إجمالي الإيرادات',
                rptCsat: 'معدل رضا العملاء',
                rptUpload: 'تاريخ رفع البيانات',
                rptTeamPerf: '📊 تحليل أداء الأقسام',
                rptKpis: 'مؤشر',
                rptOpsNotes: 'ملاحظات مدير العمليات:',
                rptWritten: '✍️ التحليل المكتوب',
                rptAnalPlh: 'اكتب تحليلك هنا لتقرير الإدارة العليا...',
                rptRec: '💡 التوصيات',
                perfExc: 'ممتاز',
                perfGood: 'جيد',
                perfAcc: 'مقبول',
                perfNeed: 'يحتاج تحسين',
                prioHigh: 'عالية',
                prioMed: 'متوسطة',
                prioLow: 'منخفضة',
                prioExc: 'ممتاز',
                recAll: 'جميع الأقسام',
                recAllGood: 'أداء ممتاز! لا توجد توصيات عاجلة لهذا الشهر.',
                recCsat: 'تراجع مؤشر رضا العملاء — يُنصح بمراجعة بروتوكولات التواصل.',
                recConv: 'معدل التحويل أقل من المستهدف — يُنصح بتطوير عروض المبيعات.',
                recOnTime: 'معدل التسليم في الوقت دون المستهدف — يُنصح بمراجعة مواعيد الشحن.',
                recTraining: 'نسبة إتمام التدريب منخفضة — يُنصح بتقييم جدولة الدورات.',
                pgAdmin: '⚙️ لوحة مدير العمليات',
                adminSpecial: 'صلاحيات خاصة',
                btnSaveAdmin: '💾 حفظ الإعدادات',
                adminIdentity: '🏢 هوية الشركة والتقارير',
                lCompany: 'اسم الشركة',
                lRptHeader: 'عنوان التقرير',
                lRptFooter: 'تذييل التقرير',
                lCurrSym: 'رمز العملة',
                lLogo: 'شعار (حرف/إيموجي)',
                adminFeatures: '🔧 الميزات والأقسام',
                featFc: 'تفعيل التوقعات',
                featRating: 'تفعيل تقييم النجوم',
                teamsVisLbl: 'الأقسام المرئية في لوحة التحكم والتقارير:',
                adminNotes: '📝 ملاحظات الأقسام (تظهر في التقارير)',
                adminLog: '📅 سجل رفع البيانات',
                btnClearLog: 'مسح السجل',
                confirmClearLog: 'مسح سجل الرفع؟',
                colMonth: 'الشهر',
                colUpDate: 'تاريخ الرفع',
                colKpiCount: 'عدد المؤشرات',
                colStatusH: 'الحالة',
                uploadOk: '● مكتمل',
                noUploads: 'لا توجد عمليات رفع بعد',
                adminKpiMgmt: '📊 إدارة مؤشرات الأداء',
                btnAddKpiAdmin: '+ إضافة مؤشر',
                colUnit: 'الوحدة',
                colHB: 'الأعلى أفضل',
                colActions: 'الإجراءات',
                adminDanger: '⚠️ منطقة الخطر',
                btnClearData: '🗑 مسح جميع البيانات',
                btnResetKpis: '🔄 إعادة ضبط المؤشرات',
                confirmClear: 'حذف جميع البيانات؟ هذا الإجراء لا يمكن التراجع عنه.',
                confirmReset: 'إعادة ضبط المؤشرات للقيم الافتراضية؟',
                clearedMsg: 'تم مسح جميع البيانات',
                resetMsg: 'تمت إعادة الضبط',
                kpiAdded: 'تم إضافة المؤشر',
                kpiUpdated: 'تم تحديث المؤشر',
                kpiDeleted: 'تم حذف المؤشر',
                kpiSaved: 'تم حفظ القيمة',
                dataSaved: 'تم حفظ البيانات',
                tplDl: 'تم تنزيل القالب',
                monthExp: 'تم تصدير بيانات الشهر',
                adminSaved: '✅ تم حفظ الإعدادات بنجاح',
                enterName: 'أدخل اسم المؤشر',
                noKpisDef: 'لا توجد مؤشرات',
                importedMsg: 'تم الاستيراد:',
                kpisWord: 'مؤشر',
                promptTarget: 'الهدف:',
                promptMonth: 'الشهر:',
                promptCurrent: 'الحالي:',
                promptEmpty: '(فارغ)',
                uploadFirst: 'انتقل إلى صفحة التقارير أولاً'
            },
            en: {
                brandSub: 'Operations Performance Dashboard',
                liveLbl: 'Live',
                lastUpd: 'Last updated:',
                tplBtn: 'Template',
                impBtn: 'Import',
                expBtn: 'Export',
                navNav: 'Navigation',
                navPeriod: 'View Period',
                navTools: 'Tools',
                navAdmin2: 'Operations Manager',
                navOverview: 'Dashboard',
                navSales: 'Sales',
                navCS: 'Customer Service',
                navCoord: 'Coordinators',
                navTraining: 'Training',
                navTrends: 'Trends',
                navForecast: 'Performance Forecast',
                navReports: 'Reports',
                navImport: 'Data Import / Export',
                navTemplate: 'Download Template',
                navPrint: 'Print Report',
                navAdminPanel: 'Admin Panel',
                sidebarFtr: 'Operations Dashboard v3.0',
                addKpiTitle: 'Add KPI Metric',
                editKpiTitle: 'Edit KPI',
                lKpiName: 'KPI Name',
                lKpiTeam: 'Team',
                lKpiUnit: 'Unit',
                lKpiTarget: 'Target Value',
                lKpiHB: 'Higher is Better?',
                lKpiIcon: 'Icon (emoji)',
                oSales: 'Sales',
                oCS: 'Customer Service',
                oCoord: 'Coordinators',
                oTraining: 'Training',
                oNumber: 'Number',
                oCurrency: 'SAR (Saudi Riyal)',
                oPercent: 'Percentage %',
                oDays: 'Days',
                oHours: 'Hours',
                oText: 'Text / Score',
                oYes: 'Yes',
                oNo: 'No',
                btnSaveKpi: 'Save KPI',
                btnCancel: 'Cancel',
                importTitle: 'Import Month Data',
                lSelMonth: 'Select Month',
                iClick: 'Click to browse',
                iDrag: 'or drag & drop',
                iFormat: 'Supports .xlsx and .xls',
                iTip: '💡 Download the template first for the pre-mapped KPI structure.',
                btnImport: 'Import Data',
                pgOverview: 'Operations Performance',
                pgOverviewSub: 'Overview',
                kpiComplete: 'KPIs complete',
                gCsat: 'CSAT Score',
                gRetention: 'Client Retention',
                gTrainingComp: 'Training Completion',
                gOnTime: 'On-Time Delivery',
                gConversion: 'Conversion Rate',
                gFCR: 'First Contact Res.',
                gsOn: 'On Target',
                gsMon: 'Monitor',
                gsRisk: 'At Risk',
                gsNoData: 'No Data',
                chMix: 'Channel Mix',
                chMixSub: 'Contact type distribution',
                chTrend: 'Contact Volume Trend',
                chTrendSub: 'Calls · Chats · Complaints',
                chRev: 'Revenue — Net vs Tax',
                chRevSub: 'SAR · Last 6 months',
                chTeam: 'Team Performance',
                chTeamSub: '% of target achieved',
                legCalls: 'Calls',
                legChats: 'Chats',
                legComplaints: 'Complaints',
                legSurveys: 'Surveys',
                legNetRev: 'Net Revenue',
                legTax: 'VAT',
                viewAll: 'View All →',
                noKpis: 'No KPIs defined.',
                pgKpi: 'KPI Metrics',
                pgEnterData: '✏ Enter Data',
                pgAddKpi: '+ KPI',
                pgRating: 'Monthly Performance Rating',
                pgNotes: 'Operations manager notes for this month...',
                pgSaveNote: 'Save Note',
                noteSaved: 'Note saved',
                pgTable: 'Data Table',
                colKpi: 'KPI',
                colActual: 'Actual',
                colTarget: 'Target',
                colPct: '% of Target',
                colStatus: 'Status',
                colDate: 'Date',
                pgVsTarget: 'Performance vs Target',
                pgDist: 'Performance Distribution',
                noKpisEmpty: 'No KPIs',
                addFirst: 'Add your first KPI.',
                btnAddKpi: '+ Add KPI',
                pctOf: '% of target',
                pgTrends: 'Trends',
                allMonths: 'All Months',
                notEnough: 'Not enough data',
                notEnoughSub: 'Import data for at least 2 months.',
                pgForecast: '🔮 Performance Forecast',
                next3: 'Next 3 Months',
                fcRefresh: '🔄 Refresh',
                fcInfoTitle: 'ℹ️ About Forecasting',
                fcInfoText: 'Forecasts are based on linear regression and moving average of available historical data. More data = more accurate forecasts.',
                fcLinear: 'Linear Regression',
                fcMA: 'Moving Average',
                fcGrowth: 'Growth Rate:',
                fcConf: 'Confidence:',
                fcBasedOn: 'Based on',
                fcMonths: 'months of data',
                fcNext3: '3-Month Forecast —',
                fcUp: 'Upward',
                fcDown: 'Downward',
                fcStable: 'Stable',
                fcHigh: 'High',
                fcMed: 'Medium',
                fcLow: 'Low',
                fcActual: 'Actual Data',
                fcLinLbl: 'Linear Forecast',
                fcMALbl: 'Moving Average',
                pgReports: '📋 Reports',
                rptSettings: '⚙️ Report Settings',
                rptType: 'Report Type',
                rptRefMonth: 'Reference Month',
                rptTeams: 'Included Teams',
                rptMonthly: 'Monthly Report',
                rptWeekly: 'Weekly Report',
                rptQuarterly: 'Quarterly Report',
                btnPDF: '🖨 Export PDF',
                btnExcel: '⬇ Excel',
                rptExec: 'Executive Summary',
                rptTotalRev: 'Total Revenue',
                rptCsat: 'CSAT Score',
                rptUpload: 'Data Upload Date',
                rptTeamPerf: '📊 Team Performance Analysis',
                rptKpis: 'KPIs',
                rptOpsNotes: 'Operations Manager Notes:',
                rptWritten: '✍️ Written Analysis',
                rptAnalPlh: 'Write your analysis here for senior management...',
                rptRec: '💡 Recommendations',
                perfExc: 'Excellent',
                perfGood: 'Good',
                perfAcc: 'Acceptable',
                perfNeed: 'Needs Improvement',
                prioHigh: 'High',
                prioMed: 'Medium',
                prioLow: 'Low',
                prioExc: 'Excellent',
                recAll: 'All Teams',
                recAllGood: 'Excellent performance! No urgent recommendations this month.',
                recCsat: 'CSAT Score declined — recommend reviewing communication protocols.',
                recConv: 'Conversion rate below target — recommend improving sales proposals.',
                recOnTime: 'On-time delivery below target — recommend reviewing shipping schedules.',
                recTraining: 'Training completion rate low — recommend reviewing session scheduling.',
                pgAdmin: '⚙️ Operations Manager Panel',
                adminSpecial: 'Special Permissions',
                btnSaveAdmin: '💾 Save Settings',
                adminIdentity: '🏢 Company & Report Identity',
                lCompany: 'Company Name',
                lRptHeader: 'Report Title',
                lRptFooter: 'Report Footer',
                lCurrSym: 'Currency Symbol',
                lLogo: 'Logo (letter/emoji)',
                adminFeatures: '🔧 Features & Departments',
                featFc: 'Enable Forecasting',
                featRating: 'Enable Star Ratings',
                teamsVisLbl: 'Departments visible in dashboard and reports:',
                adminNotes: '📝 Team Notes (shown in reports)',
                adminLog: '📅 Data Upload Log',
                btnClearLog: 'Clear Log',
                confirmClearLog: 'Clear upload log?',
                colMonth: 'Month',
                colUpDate: 'Upload Date',
                colKpiCount: 'KPI Count',
                colStatusH: 'Status',
                uploadOk: '● Complete',
                noUploads: 'No uploads yet',
                adminKpiMgmt: '📊 KPI Management',
                btnAddKpiAdmin: '+ Add KPI',
                colUnit: 'Unit',
                colHB: 'Higher Better',
                colActions: 'Actions',
                adminDanger: '⚠️ Danger Zone',
                btnClearData: '🗑 Clear All Data',
                btnResetKpis: '🔄 Reset KPIs',
                confirmClear: 'Delete all data? This cannot be undone.',
                confirmReset: 'Reset KPIs to default values?',
                clearedMsg: 'All data cleared',
                resetMsg: 'KPIs reset',
                kpiAdded: 'KPI added',
                kpiUpdated: 'KPI updated',
                kpiDeleted: 'KPI deleted',
                kpiSaved: 'Value saved',
                dataSaved: 'Data saved',
                tplDl: 'Template downloaded',
                monthExp: 'Month exported',
                adminSaved: '✅ Settings saved',
                enterName: 'Please enter a KPI name',
                noKpisDef: 'No KPIs defined',
                importedMsg: 'Imported:',
                kpisWord: 'KPIs',
                promptTarget: 'Target:',
                promptMonth: 'Month:',
                promptCurrent: 'Current:',
                promptEmpty: '(empty)',
                uploadFirst: 'Please navigate to the Reports page first'
            }
        };

        function T(k) {
            return (TRANS[lang] && TRANS[lang][k]) || TRANS.ar[k] || k;
        }

        // ═══════════════════ STATE ═══════════════════
        var lang = localStorage.getItem('miran_lang') || 'ar';
        var activeTeam = 'overview';
        var editingKpiId = null;
        var pendingImportData = null;
        var dragSrcId = null;
        var ci = {};

        function buildMonths() {
            var ms = [];
            for (var y = 2025; y <= 2030; y++) {
                for (var m = 0; m < 12; m++) {
                    ms.push({
                        key: y + '-' + (m < 9 ? '0' : '') + (m + 1),
                        labelAr: MN_AR[m] + ' ' + y,
                        labelEn: MN_EN[m] + ' ' + y,
                        year: y,
                        month: m
                    });
                }
            }
            return ms;
        }
        var ML = buildMonths();

        function DEFAULT_KPIS() {
            return [{
                    id: 's1',
                    name: 'إجمالي الإيرادات',
                    nameEn: 'Total Revenue',
                    team: 'sales',
                    unit: 'currency',
                    target: 1875000,
                    higherBetter: true,
                    order: 0,
                    icon: '💰'
                },
                {
                    id: 's2',
                    name: 'عملاء جدد',
                    nameEn: 'New Clients',
                    team: 'sales',
                    unit: 'number',
                    target: 20,
                    higherBetter: true,
                    order: 1,
                    icon: '🤝'
                },
                {
                    id: 's3',
                    name: 'صفقات مغلقة',
                    nameEn: 'Deals Closed',
                    team: 'sales',
                    unit: 'number',
                    target: 40,
                    higherBetter: true,
                    order: 2,
                    icon: '✅'
                },
                {
                    id: 's4',
                    name: 'متوسط قيمة الصفقة',
                    nameEn: 'Avg Deal Value',
                    team: 'sales',
                    unit: 'currency',
                    target: 46875,
                    higherBetter: true,
                    order: 3,
                    icon: '📊'
                },
                {
                    id: 's5',
                    name: 'قيمة المسار البيعي',
                    nameEn: 'Pipeline Value',
                    team: 'sales',
                    unit: 'currency',
                    target: 5625000,
                    higherBetter: true,
                    order: 4,
                    icon: '🔮'
                },
                {
                    id: 's6',
                    name: 'معدل التحويل',
                    nameEn: 'Conversion Rate',
                    team: 'sales',
                    unit: 'percent',
                    target: 35,
                    higherBetter: true,
                    order: 5,
                    icon: '🎯'
                },
                {
                    id: 's7',
                    name: 'عروض أسعار مرسلة',
                    nameEn: 'Proposals Sent',
                    team: 'sales',
                    unit: 'number',
                    target: 60,
                    higherBetter: true,
                    order: 6,
                    icon: '📨'
                },
                {
                    id: 's8',
                    name: 'صفقات خسارة',
                    nameEn: 'Lost Deals',
                    team: 'sales',
                    unit: 'number',
                    target: 10,
                    higherBetter: false,
                    order: 7,
                    icon: '❌'
                },
                {
                    id: 'c1',
                    name: 'معدل رضا العملاء',
                    nameEn: 'CSAT Score',
                    team: 'cs',
                    unit: 'percent',
                    target: 90,
                    higherBetter: true,
                    order: 0,
                    icon: '⭐'
                },
                {
                    id: 'c2',
                    name: 'تذاكر محلولة',
                    nameEn: 'Tickets Resolved',
                    team: 'cs',
                    unit: 'number',
                    target: 200,
                    higherBetter: true,
                    order: 1,
                    icon: '🎫'
                },
                {
                    id: 'c3',
                    name: 'متوسط وقت الاستجابة',
                    nameEn: 'Avg Response Time',
                    team: 'cs',
                    unit: 'hours',
                    target: 4,
                    higherBetter: false,
                    order: 2,
                    icon: '⏱'
                },
                {
                    id: 'c4',
                    name: 'حل من أول تواصل',
                    nameEn: 'First Contact Resolution',
                    team: 'cs',
                    unit: 'percent',
                    target: 75,
                    higherBetter: true,
                    order: 3,
                    icon: '🎖'
                },
                {
                    id: 'c5',
                    name: 'التصعيدات',
                    nameEn: 'Escalations',
                    team: 'cs',
                    unit: 'number',
                    target: 10,
                    higherBetter: false,
                    order: 4,
                    icon: '⚠️'
                },
                {
                    id: 'c6',
                    name: 'معدل الاحتفاظ بالعملاء',
                    nameEn: 'Client Retention Rate',
                    team: 'cs',
                    unit: 'percent',
                    target: 95,
                    higherBetter: true,
                    order: 5,
                    icon: '♻️'
                },
                {
                    id: 'c7',
                    name: 'مؤشر NPS',
                    nameEn: 'NPS Score',
                    team: 'cs',
                    unit: 'number',
                    target: 50,
                    higherBetter: true,
                    order: 6,
                    icon: '📡'
                },
                {
                    id: 'c8',
                    name: 'إجمالي المكالمات',
                    nameEn: 'Total Calls',
                    team: 'cs',
                    unit: 'number',
                    target: 1200,
                    higherBetter: true,
                    order: 7,
                    icon: '📞'
                },
                {
                    id: 'c9',
                    name: 'تفاعلات الدردشة',
                    nameEn: 'Chat Interactions',
                    team: 'cs',
                    unit: 'number',
                    target: 5000,
                    higherBetter: true,
                    order: 8,
                    icon: '💬'
                },
                {
                    id: 'o1',
                    name: 'شحنات مُعالجة',
                    nameEn: 'Shipments Processed',
                    team: 'coord',
                    unit: 'number',
                    target: 150,
                    higherBetter: true,
                    order: 0,
                    icon: '🚢'
                },
                {
                    id: 'o2',
                    name: 'معدل التسليم في الوقت',
                    nameEn: 'On-Time Delivery',
                    team: 'coord',
                    unit: 'percent',
                    target: 95,
                    higherBetter: true,
                    order: 1,
                    icon: '⏰'
                },
                {
                    id: 'o3',
                    name: 'دقة التوثيق',
                    nameEn: 'Doc Accuracy',
                    team: 'coord',
                    unit: 'percent',
                    target: 99,
                    higherBetter: true,
                    order: 2,
                    icon: '📄'
                },
                {
                    id: 'o4',
                    name: 'متوسط وقت المعالجة',
                    nameEn: 'Avg Processing Time',
                    team: 'coord',
                    unit: 'days',
                    target: 3,
                    higherBetter: false,
                    order: 3,
                    icon: '🔄'
                },
                {
                    id: 'o5',
                    name: 'معدل التخليص الجمركي',
                    nameEn: 'Customs Clearance',
                    team: 'coord',
                    unit: 'percent',
                    target: 98,
                    higherBetter: true,
                    order: 4,
                    icon: '🛃'
                },
                {
                    id: 'o6',
                    name: 'شكاوى الموردين',
                    nameEn: 'Vendor Complaints',
                    team: 'coord',
                    unit: 'number',
                    target: 5,
                    higherBetter: false,
                    order: 5,
                    icon: '🔔'
                },
                {
                    id: 'o7',
                    name: 'تكلفة الشحنة الواحدة',
                    nameEn: 'Cost per Shipment',
                    team: 'coord',
                    unit: 'currency',
                    target: 3187,
                    higherBetter: false,
                    order: 6,
                    icon: '💵'
                },
                {
                    id: 't1',
                    name: 'جلسات تدريبية',
                    nameEn: 'Training Sessions',
                    team: 'training',
                    unit: 'number',
                    target: 8,
                    higherBetter: true,
                    order: 0,
                    icon: '📚'
                },
                {
                    id: 't2',
                    name: 'موظفون مدربون',
                    nameEn: 'Staff Trained',
                    team: 'training',
                    unit: 'number',
                    target: 30,
                    higherBetter: true,
                    order: 1,
                    icon: '👥'
                },
                {
                    id: 't3',
                    name: 'متوسط درجة التقييم',
                    nameEn: 'Avg Assessment Score',
                    team: 'training',
                    unit: 'percent',
                    target: 80,
                    higherBetter: true,
                    order: 2,
                    icon: '📝'
                },
                {
                    id: 't4',
                    name: 'معدل إكمال التدريب',
                    nameEn: 'Training Completion',
                    team: 'training',
                    unit: 'percent',
                    target: 90,
                    higherBetter: true,
                    order: 3,
                    icon: '🏆'
                },
                {
                    id: 't5',
                    name: 'شهادات صادرة',
                    nameEn: 'Certifications Issued',
                    team: 'training',
                    unit: 'number',
                    target: 15,
                    higherBetter: true,
                    order: 4,
                    icon: '🎓'
                },
                {
                    id: 't6',
                    name: 'الاحتفاظ بالمعرفة',
                    nameEn: 'Knowledge Retention',
                    team: 'training',
                    unit: 'percent',
                    target: 85,
                    higherBetter: true,
                    order: 5,
                    icon: '🧠'
                },
            ];
        }

        function defaultAdmin() {
            return {
                companyName: 'Miran OPS Performance',
                reportHeader: 'تقرير الأداء التشغيلي',
                reportFooter: 'سري - للاستخدام الداخلي فقط',
                enableForecasting: true,
                enableRatings: true,
                currencySymbol: 'ر.س',
                teamsVisible: {
                    sales: true,
                    cs: true,
                    coord: true,
                    training: true
                },
                notesPerTeam: {
                    sales: '',
                    cs: '',
                    coord: '',
                    training: ''
                }
            };
        }

        function loadState() {
            try {
                return {
                    kpis: JSON.parse(localStorage.getItem('m_kpis') || 'null') || DEFAULT_KPIS(),
                    data: JSON.parse(localStorage.getItem('m_data') || '{}'),
                    activeMonth: localStorage.getItem('m_month') || '2025-01',
                    uploadLog: JSON.parse(localStorage.getItem('m_uploads') || '[]'),
                    adminSettings: JSON.parse(localStorage.getItem('m_admin') || 'null') || defaultAdmin(),
                    ratings: JSON.parse(localStorage.getItem('m_ratings') || '{}')
                };
            } catch (e) {
                return {
                    kpis: DEFAULT_KPIS(),
                    data: {},
                    activeMonth: '2025-01',
                    uploadLog: [],
                    adminSettings: defaultAdmin(),
                    ratings: {}
                };
            }
        }

        function saveState() {
            localStorage.setItem('m_kpis', JSON.stringify(state.kpis));
            localStorage.setItem('m_data', JSON.stringify(state.data));
            localStorage.setItem('m_month', state.activeMonth);
            localStorage.setItem('m_uploads', JSON.stringify(state.uploadLog));
            localStorage.setItem('m_admin', JSON.stringify(state.adminSettings));
            localStorage.setItem('m_ratings', JSON.stringify(state.ratings));
        }
        var state = loadState();

        // ═══════════════════ HELPERS ═══════════════════
        function fmt(v, u) {
            if (v === null || v === undefined || v === '') return '—';
            var n = parseFloat(v);
            if (isNaN(n)) return v;
            var sym = state.adminSettings.currencySymbol || 'ر.س';
            if (u === 'currency') return n.toLocaleString('en-US', {
                maximumFractionDigits: 0
            }) + ' ' + sym;
            if (u === 'percent') return n.toFixed(1) + '%';
            if (u === 'days') return n.toFixed(1) + (lang === 'ar' ? ' يوم' : ' d');
            if (u === 'hours') return n.toFixed(1) + (lang === 'ar' ? ' ساعة' : ' h');
            return n.toLocaleString('en-US', {
                maximumFractionDigits: 2
            });
        }

        function fmtS(v, u) {
            if (v === null || v === undefined || v === '') return '—';
            var n = parseFloat(v);
            if (isNaN(n)) return v;
            var sym = state.adminSettings.currencySymbol || 'ر.س';
            if (u === 'currency') {
                if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M ' + sym;
                if (n >= 1000) return (n / 1000).toFixed(0) + 'K ' + sym;
                return n + ' ' + sym;
            }
            if (u === 'percent') return n.toFixed(1) + '%';
            if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
            return n.toLocaleString('en-US', {
                maximumFractionDigits: 1
            });
        }

        function tc(t) {
            return {
                sales: '#F0A500',
                cs: '#4F8EF7',
                coord: '#A855F7',
                training: '#FB923C'
            } [t] || '#00D4AA';
        }

        function tcd(t) {
            return {
                sales: 'rgba(240,165,0,0.15)',
                cs: 'rgba(79,142,247,0.15)',
                coord: 'rgba(168,85,247,0.15)',
                training: 'rgba(251,146,60,0.15)'
            } [t] || 'rgba(0,212,170,0.15)';
        }

        function tl(t) {
            var a = {
                sales: T('oSales'),
                cs: T('oCS'),
                coord: T('oCoord'),
                training: T('oTraining')
            };
            return a[t] || t;
        }

        function ml(m) {
            return lang === 'ar' ? m.labelAr : m.labelEn;
        }

        function getMeta() {
            return ML.find(function(m) {
                return m.key === state.activeMonth;
            }) || ML[0];
        }

        function killCharts() {
            Object.keys(ci).forEach(function(k) {
                try {
                    ci[k].destroy();
                } catch (e) {}
            });
            ci = {};
        }

        function getDelta(kpi, val) {
            var idx = ML.findIndex(function(m) {
                return m.key === state.activeMonth;
            });
            if (idx <= 0) return null;
            var prev = parseFloat((state.data[ML[idx - 1].key] || {})[kpi.id]);
            var cur = parseFloat(val);
            if (isNaN(prev) || isNaN(cur) || prev === 0) return null;
            return {
                pct: ((cur - prev) / Math.abs(prev) * 100).toFixed(1),
                up: cur >= prev
            };
        }

        function pctTgt(kpi, val) {
            var n = parseFloat(val),
                t = parseFloat(kpi.target);
            if (isNaN(n) || !t) return 0;
            return Math.min((n / t) * 100, 100);
        }

        function kpiSt(kpi, val) {
            var p = pctTgt(kpi, val);
            if (p >= 90) return {
                cls: 'gs-on',
                lbl: T('gsOn')
            };
            if (p >= 70) return {
                cls: 'gs-monitor',
                lbl: T('gsMon')
            };
            return {
                cls: 'gs-risk',
                lbl: T('gsRisk')
            };
        }

        function getUploadDate(k) {
            var l = state.uploadLog.find(function(u) {
                return u.monthKey === k;
            });
            return l ? l.date : null;
        }

        function toast(msg) {
            var t = document.getElementById('toast');
            t.textContent = msg;
            t.classList.add('show');
            setTimeout(function() {
                t.classList.remove('show');
            }, 3000);
        }

        function el(id) {
            return document.getElementById(id);
        }

        function setTxt(id, txt) {
            var e = el(id);
            if (e) e.textContent = txt;
        }

        // ═══════════════════ FORECASTING ═══════════════════
        function getHistVals(kpiId, n) {
            var idx = ML.findIndex(function(m) {
                return m.key === state.activeMonth;
            });
            var vals = [];
            for (var i = Math.max(0, idx - n); i <= idx; i++) {
                var v = (state.data[ML[i].key] || {})[kpiId];
                if (v !== undefined && v !== '') vals.push({
                    month: ML[i],
                    val: parseFloat(v)
                });
            }
            return vals;
        }

        function linForecast(vals, steps) {
            if (vals.length < 2) return null;
            var n = vals.length,
                xs = [],
                ys = [];
            vals.forEach(function(v, i) {
                xs.push(i);
                ys.push(v.val);
            });
            var xm = xs.reduce(function(a, b) {
                    return a + b;
                }, 0) / n,
                ym = ys.reduce(function(a, b) {
                    return a + b;
                }, 0) / n;
            var num = 0,
                den = 0;
            xs.forEach(function(x, i) {
                num += (x - xm) * (ys[i] - ym);
                den += (x - xm) * (x - xm);
            });
            if (!den) return ym;
            var slope = num / den,
                intc = ym - slope * xm;
            return intc + slope * (n - 1 + steps);
        }

        function maForecast(vals, w) {
            if (!vals.length) return null;
            if (w > vals.length) w = vals.length;
            return vals.slice(-w).reduce(function(a, b) {
                return a + b.val;
            }, 0) / w;
        }

        function growthRate(vals) {
            if (vals.length < 2) return 0;
            var f = vals[0].val,
                l = vals[vals.length - 1].val;
            if (!f) return 0;
            return ((l - f) / Math.abs(f)) * 100;
        }

        function forecastKpi(kpiId, steps) {
            var vals = getHistVals(kpiId, 12);
            if (!vals.length) return {
                linear: null,
                ma: null,
                growth: 0,
                confidence: T('fcLow'),
                trend: T('fcStable'),
                histVals: vals
            };
            var lin = linForecast(vals, steps),
                ma = maForecast(vals, 3),
                gr = growthRate(vals);
            var conf = vals.length >= 6 ? T('fcHigh') : vals.length >= 3 ? T('fcMed') : T('fcLow');
            var trend = gr > 5 ? T('fcUp') : gr < -5 ? T('fcDown') : T('fcStable');
            return {
                linear: lin,
                ma: ma,
                growth: gr,
                confidence: conf,
                trend: trend,
                histVals: vals
            };
        }

        // ═══════════════════ LANGUAGE ═══════════════════
        function setLang(l) {
            lang = l;
            localStorage.setItem('miran_lang', l);
            el('btnEn').classList.toggle('active', l === 'en');
            el('btnAr').classList.toggle('active', l === 'ar');
            document.body.classList.toggle('ltr', l === 'en');
            document.documentElement.lang = l;
            document.documentElement.dir = l === 'ar' ? 'rtl' : 'ltr';
            applyStaticTrans();
            renderSidebar();
            renderMain();
        }

        function applyStaticTrans() {
            var isAr = lang === 'ar';
            var now = new Date();
            setTxt('brandSub', T('brandSub'));
            setTxt('liveLbl', T('liveLbl'));
            setTxt('tTpl', T('tplBtn'));
            setTxt('tImp', T('impBtn'));
            setTxt('tExp', T('expBtn'));
            setTxt('liveDate', now.toLocaleDateString(isAr ? 'ar-SA' : 'en-US', {
                month: 'short',
                year: 'numeric'
            }));
            setTxt('lastUpdTxt', T('lastUpd') + ' ' + now.toLocaleTimeString(isAr ? 'ar-SA' : 'en-US', {
                hour: '2-digit',
                minute: '2-digit'
            }));
            // KPI Modal
            setTxt('lKpiName', T('lKpiName'));
            setTxt('lKpiTeam', T('lKpiTeam'));
            setTxt('lKpiUnit', T('lKpiUnit'));
            setTxt('lKpiTarget', T('lKpiTarget'));
            setTxt('lKpiHB', T('lKpiHB'));
            setTxt('lKpiIcon', T('lKpiIcon'));
            setTxt('oSales', T('oSales'));
            setTxt('oCS', T('oCS'));
            setTxt('oCoord', T('oCoord'));
            setTxt('oTraining', T('oTraining'));
            setTxt('oNumber', T('oNumber'));
            setTxt('oCurrency', T('oCurrency'));
            setTxt('oPercent', T('oPercent'));
            setTxt('oDays', T('oDays'));
            setTxt('oHours', T('oHours'));
            setTxt('oText', T('oText'));
            setTxt('oYes', T('oYes'));
            setTxt('oNo', T('oNo'));
            setTxt('btnSaveKpi', T('btnSaveKpi'));
            setTxt('btnCancelKpi', T('btnCancel'));
            // Import Modal
            setTxt('importModalTitle', T('importTitle'));
            setTxt('lSelMonth', T('lSelMonth'));
            setTxt('iClick', T('iClick'));
            setTxt('iDrag', T('iDrag'));
            setTxt('iFormat', T('iFormat'));
            setTxt('iTip', T('iTip'));
            setTxt('btnCancelImport', T('btnCancel'));
            var cib = el('confirmImportBtn');
            if (cib) setTxt('confirmImportBtn', T('btnImport'));
        }

        // ═══════════════════ SIDEBAR ═══════════════════
        function renderSidebar() {
            var isAr = lang === 'ar';
            var dir = isAr ? 'row-reverse' : 'row';
            var meta = getMeta();
            var counts = {};
            ['sales', 'cs', 'coord', 'training'].forEach(function(t) {
                var kpis = state.kpis.filter(function(k) {
                    return k.team === t;
                });
                var md = state.data[state.activeMonth] || {};
                var filled = kpis.filter(function(k) {
                    return md[k.id] !== undefined && md[k.id] !== '';
                }).length;
                counts[t] = filled + '/' + kpis.length;
            });

            function ni(team, icon, label, count, isAdmin) {
                var active = activeTeam === team;
                var cls = 'nav-item' + (active ? ' active' : '') + (isAdmin ? ' admin-nav' : '');
                var bg = isAdmin && active ? 'background:rgba(168,85,247,0.15)' : '';
                var cnt = count ? '<span class="nav-count" style="margin-' + (isAr ? 'right' : 'left') + ':auto">' + count +
                    '</span>' : '';
                return '<div class="' + cls + '" style="flex-direction:' + dir + '" onclick="selectTeam(\'' + team +
                    '\',this)"><div class="nav-icon" style="' + bg + '">' + icon + '</div><span>' + label + '</span>' +
                    cnt + '</div>';
            }
            var html = '<div class="nav-section">' +
                '<div class="nav-label">' + T('navNav') + '</div>' +
                ni('overview', '📊', T('navOverview')) +
                ni('sales', '💰', T('navSales'), counts.sales) +
                ni('cs', '🎧', T('navCS'), counts.cs) +
                ni('coord', '📦', T('navCoord'), counts.coord) +
                ni('training', '🎓', T('navTraining'), counts.training) +
                ni('trends', '📈', T('navTrends')) +
                ni('forecast', '🔮', T('navForecast')) +
                ni('reports', '📋', T('navReports')) +
                '</div>' +
                '<div class="sidebar-div"></div>' +
                '<div class="mth-section">' +
                '<div class="nav-label">' + T('navPeriod') + '</div>' +
                '<button class="mth-btn" id="mthBtn" onclick="toggleMthPanel()"><span id="mthLabel">' + ml(meta) +
                '</span><span class="chevron">▼</span></button>' +
                '<div class="mth-panel" id="mthPanel"></div>' +
                '</div>' +
                '<div class="sidebar-div"></div>' +
                '<div class="nav-section" style="padding-bottom:6px">' +
                '<div class="nav-label">' + T('navTools') + '</div>' +
                '<div class="nav-item" style="flex-direction:' + dir +
                '" onclick="openImportModal()"><div class="nav-icon">📥</div><span>' + T('navImport') + '</span></div>' +
                '<div class="nav-item" style="flex-direction:' + dir +
                '" onclick="downloadTemplate()"><div class="nav-icon">📋</div><span>' + T('navTemplate') + '</span></div>' +
                '<div class="nav-item" style="flex-direction:' + dir +
                '" onclick="window.print()"><div class="nav-icon">🖨</div><span>' + T('navPrint') + '</span></div>' +
                '</div>' +
                '<div class="sidebar-div"></div>' +
                '<div class="nav-section" style="padding-bottom:6px">' +
                '<div class="nav-label" style="color:rgba(168,85,247,0.8)">' + T('navAdmin2') + '</div>' +
                ni('admin', '⚙️', T('navAdminPanel'), null, true) +
                '</div>' +
                '<div class="sidebar-footer" style="text-align:' + (isAr ? 'right' : 'left') + '">' +
                '<img src="' + el('logoImg').src +
                '" alt="Miran" style="height:26px;width:auto;object-fit:contain;margin-bottom:6px;display:block">' +
                'Miran OPS Performance<br>' + T('sidebarFtr') +
                '</div>';
            el('sidebarEl').innerHTML = html;
            renderMthPanel();
        }

        function renderMthPanel() {
            var isAr = lang === 'ar';
            var panel = el('mthPanel');
            if (!panel) return;
            var html = '';
            ML.forEach(function(m) {
                var hasData = state.data[m.key] && Object.keys(state.data[m.key]).length > 0;
                var active = m.key === state.activeMonth;
                html += '<div class="mth-item' + (active ? ' active' : '') + '" style="flex-direction:' + (isAr ?
                        'row-reverse' : 'row') + '" onclick="selectMonth(\'' + m.key + '\')">' +
                    '<span>' + ml(m) + '</span><span class="mdot ' + (hasData ? 'has' : 'empty') +
                    '"></span></div>';
            });
            panel.innerHTML = html;
        }

        function toggleMthPanel() {
            el('mthBtn').classList.toggle('open');
            el('mthPanel').classList.toggle('open');
        }

        function selectMonth(key) {
            state.activeMonth = key;
            saveState();
            el('mthBtn').classList.remove('open');
            el('mthPanel').classList.remove('open');
            renderSidebar();
            renderMain();
        }

        function selectTeam(team, elem) {
            activeTeam = team;
            document.querySelectorAll('.nav-item').forEach(function(n) {
                n.classList.remove('active');
            });
            if (elem) elem.classList.add('active');
            renderMain();
        }

        // ═══════════════════ ROUTER ═══════════════════
        function renderMain() {
            killCharts();
            var meta = getMeta();
            var main = el('mainArea');
            if (activeTeam === 'overview') renderOverview(main, meta);
            else if (activeTeam === 'trends') renderTrends(main, meta);
            else if (activeTeam === 'forecast') renderForecast(main, meta);
            else if (activeTeam === 'reports') renderReports(main, meta);
            else if (activeTeam === 'admin') renderAdmin(main);
            else renderTeamPage(main, activeTeam, meta);
        }

        // ═══════════════════ OVERVIEW ═══════════════════
        function renderOverview(main, meta) {
            var md = state.data[meta.key] || {};
            var isAr = lang === 'ar';
            var align = isAr ? 'right' : 'left';
            main.innerHTML = '<div class="page-hdr">' +
                '<div class="page-hdr-left"><div class="page-title">' + T('pgOverview') + '</div><div class="month-chip">' +
                ml(meta) + '</div></div>' +
                '<div class="page-hdr-right">' +
                '<button class="btn btn-ghost" onclick="addKpiModal()">+ ' + T('pgAddKpi').replace('+ ', '') + '</button>' +
                '<button class="btn btn-ghost" onclick="openImportModal()">⬆ ' + T('navImport').split('/')[0].trim() +
                '</button>' +
                '</div></div>' +
                '<div class="sec-hdr"><div class="sec-title">' + T('pgOverviewSub') + '</div></div>' +
                '<div class="panels-row" id="panelsRow"></div>' +
                '<div class="gauges-sec"><div class="sec-hdr" style="margin-bottom:16px"><div class="sec-title">' + (isAr ?
                    'مقاييس الأداء الرئيسية' : 'Key Rate Gauges') +
                '</div></div><div class="gauges-grid" id="gaugesGrid"></div></div>' +
                '<div class="charts-grid" id="mainChartsGrid"></div>' +
                '<div id="teamSections"></div>';
            renderPanelCards(md, meta);
            renderGauges(md);
            renderOverviewCharts(md, meta);
            renderTeamSections(md);
        }

        function renderPanelCards(md, meta) {
            var isAr = lang === 'ar';
            var dir = isAr ? 'row-reverse' : 'row';
            var align = isAr ? 'right' : 'left';
            var subAr = {
                sales: 'الإيرادات والمبيعات',
                cs: 'رضا العملاء والتذاكر',
                coord: 'الشحنات والتسليم',
                training: 'التدريب والاكتمال'
            };
            var subEn = {
                sales: 'Revenue & Pipeline',
                cs: 'CSAT & Tickets',
                coord: 'Shipments & Delivery',
                training: 'Training & Completion'
            };
            var cfgs = [{
                id: 'sales',
                c: 'c-gold',
                icon: '💰'
            }, {
                id: 'cs',
                c: 'c-blue',
                icon: '🎧'
            }, {
                id: 'coord',
                c: 'c-purple',
                icon: '📦'
            }, {
                id: 'training',
                c: 'c-orange',
                icon: '🎓'
            }];
            var row = el('panelsRow');
            row.innerHTML = '';
            cfgs.forEach(function(cfg) {
                var kpis = state.kpis.filter(function(k) {
                    return k.team === cfg.id;
                }).sort(function(a, b) {
                    return a.order - b.order;
                });
                var filled = kpis.filter(function(k) {
                    return md[k.id] !== undefined && md[k.id] !== '';
                });
                var top = kpis[0];
                var topVal = top ? md[top.id] : '';
                var pct = kpis.length ? Math.round(filled.length / kpis.length * 100) : 0;
                var sub = isAr ? subAr[cfg.id] : subEn[cfg.id];
                var div = document.createElement('div');
                div.className = 'panel-card ' + cfg.c;
                div.onclick = function() {
                    selectTeam(cfg.id, el('nav-' + cfg.id));
                };
                div.innerHTML =
                    '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;flex-direction:' +
                    dir + '">' +
                    '<div style="font-size:13px;font-weight:700">' + tl(cfg.id) +
                    '</div><div style="font-size:18px">' + cfg.icon + '</div></div>' +
                    '<div style="font-size:10px;color:var(--text3);margin-bottom:10px;text-align:' + align + '">' +
                    sub + '</div>' +
                    (top ? '<div style="font-size:22px;font-weight:700;color:' + tc(cfg.id) + ';text-align:' +
                        align + '">' + fmtS(topVal, top.unit) + '</div>' +
                        '<div style="font-size:10px;color:var(--text3);text-align:' + align +
                        ';margin-bottom:6px">' + top.name + '</div>' : '') +
                    '<div style="font-size:10px;color:var(--text2);text-align:' + align + '">' + filled.length +
                    '/' + kpis.length + ' ' + T('kpiComplete') + '</div>' +
                    '<div class="kpi-prog" style="margin-top:8px"><div class="kpi-prog-bar" style="width:' + pct +
                    '%;background:' + tc(cfg.id) + '"></div></div>' +
                    '<div style="height:40px;position:relative;margin-top:8px"><canvas id="sp-' + cfg.id +
                    '" role="img" aria-label="' + tl(cfg.id) + '"></canvas></div>';
                row.appendChild(div);
                (function(id, team, kps) {
                    setTimeout(function() {
                        var canvas = el('sp-' + id);
                        if (!canvas) return;
                        var kpi = kps[0];
                        if (!kpi) return;
                        var vals = ML.slice(-6).map(function(m) {
                            var v = (state.data[m.key] || {})[kpi.id];
                            return v ? parseFloat(v) : null;
                        });
                        ci['sp-' + id] = new Chart(canvas, {
                            type: 'line',
                            data: {
                                labels: ML.slice(-6).map(function(m) {
                                    return ml(m).slice(0, 3);
                                }),
                                datasets: [{
                                    data: vals,
                                    borderColor: tc(team),
                                    borderWidth: 2,
                                    pointRadius: 0,
                                    fill: true,
                                    backgroundColor: tcd(team),
                                    tension: .4,
                                    spanGaps: true
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        enabled: false
                                    }
                                },
                                scales: {
                                    x: {
                                        display: false
                                    },
                                    y: {
                                        display: false
                                    }
                                }
                            }
                        });
                    }, 60);
                })(cfg.id, cfg.id, kpis);
            });
        }

        function renderGauges(md) {
            var gKpis = [{
                id: 'c1',
                k: 'gCsat'
            }, {
                id: 'c6',
                k: 'gRetention'
            }, {
                id: 't4',
                k: 'gTrainingComp'
            }, {
                id: 'o2',
                k: 'gOnTime'
            }, {
                id: 's6',
                k: 'gConversion'
            }, {
                id: 'c4',
                k: 'gFCR'
            }];
            var grid = el('gaugesGrid');
            grid.innerHTML = '';
            gKpis.forEach(function(gk) {
                var kpi = state.kpis.find(function(k) {
                    return k.id === gk.id;
                });
                if (!kpi) return;
                var val = md[kpi.id];
                var pct = val !== undefined && val !== '' ? Math.min(parseFloat(val) / parseFloat(kpi.target) * 100,
                    100) : 0;
                var st = val !== undefined ? kpiSt(kpi, val) : {
                    cls: 'gs-monitor',
                    lbl: T('gsNoData')
                };
                var color = st.cls === 'gs-on' ? '#4ADE80' : st.cls === 'gs-monitor' ? '#F0A500' : '#FF6B6B';
                var cid = 'gv-' + gk.id;
                var div = document.createElement('div');
                div.className = 'gauge-wrap';
                div.innerHTML =
                    '<div style="position:relative;width:140px;height:84px;overflow:hidden"><canvas id="' + cid +
                    '" width="140" height="84" role="img" aria-label="' + T(gk.k) + '"></canvas></div>' +
                    '<div class="gauge-val" style="color:' + color + '">' + (val !== undefined && val !== '' ? fmt(
                        val, kpi.unit) : '—') + '</div>' +
                    '<div class="gauge-lbl">' + T(gk.k) + '</div>' +
                    '<div class="gauge-status ' + st.cls + '">' + (st.cls === 'gs-on' ? '●' : '▲') + ' ' + st.lbl +
                    '</div>';
                grid.appendChild(div);
                (function(cid, pct, color) {
                    setTimeout(function() {
                        drawGauge(cid, pct, color);
                    }, 50);
                })(cid, pct, color);
            });
        }

        function drawGauge(id, pct, color) {
            var canvas = el(id);
            if (!canvas) return;
            var ctx = canvas.getContext('2d');
            var cx = 70,
                cy = 76,
                r = 54;
            ctx.clearRect(0, 0, 140, 84);
            ctx.beginPath();
            ctx.arc(cx, cy, r, Math.PI, 2 * Math.PI);
            ctx.strokeStyle = 'rgba(255,255,255,0.05)';
            ctx.lineWidth = 11;
            ctx.lineCap = 'round';
            ctx.stroke();
            var fill = Math.PI + (pct / 100) * Math.PI;
            ctx.beginPath();
            ctx.arc(cx, cy, r, Math.PI, fill);
            ctx.strokeStyle = color;
            ctx.lineWidth = 11;
            ctx.lineCap = 'round';
            ctx.stroke();
            ctx.fillStyle = 'rgba(255,255,255,0.2)';
            ctx.font = '9px Inter';
            ctx.textAlign = 'left';
            ctx.fillText('0', cx - r - 8, cy + 5);
            ctx.textAlign = 'right';
            ctx.fillText('100', cx + r + 8, cy + 5);
        }

        function renderOverviewCharts(md, meta) {
            var grid = el('mainChartsGrid');
            var calls = parseFloat(md.c8 || 1100),
                chats = parseFloat(md.c9 || 5030),
                complaints = parseFloat(md.c5 || 55),
                surveys = 350;
            grid.innerHTML = '<div class="chart-card">' +
                '<div class="sec-hdr" style="margin-bottom:10px"><div><div class="sec-title">' + T('chMix') +
                '</div><div style="font-size:10px;color:var(--text3)">' + T('chMixSub') + '</div></div></div>' +
                '<div class="chart-wrap" style="height:170px"><canvas id="chMix" role="img" aria-label="' + T('chMix') +
                '"></canvas></div>' +
                '<div class="chart-legend" id="chMixLeg"></div></div>' +
                '<div class="chart-card">' +
                '<div class="sec-hdr" style="margin-bottom:10px"><div><div class="sec-title">' + T('chTrend') +
                '</div><div style="font-size:10px;color:var(--text3)">' + T('chTrendSub') + '</div></div></div>' +
                '<div class="chart-wrap" style="height:200px"><canvas id="cvTrend" role="img" aria-label="' + T('chTrend') +
                '"></canvas></div>' +
                '<div class="chart-legend" id="cvLeg"></div></div>' +
                '<div class="chart-card">' +
                '<div class="sec-hdr" style="margin-bottom:10px"><div><div class="sec-title">' + T('chRev') +
                '</div><div style="font-size:10px;color:var(--text3)">' + T('chRevSub') + '</div></div></div>' +
                '<div class="chart-wrap" style="height:180px"><canvas id="revChart" role="img" aria-label="' + T('chRev') +
                '"></canvas></div>' +
                '<div class="chart-legend" id="revLeg"></div></div>' +
                '<div class="chart-card">' +
                '<div class="sec-hdr" style="margin-bottom:10px"><div><div class="sec-title">' + T('chTeam') +
                '</div><div style="font-size:10px;color:var(--text3)">' + T('chTeamSub') + '</div></div></div>' +
                '<div class="chart-wrap" style="height:180px"><canvas id="teamRadar" role="img" aria-label="' + T(
                'chTeam') + '"></canvas></div></div>';
            var tot = calls + chats + complaints + surveys || 1;
            ci.chMix = new Chart(el('chMix'), {
                type: 'doughnut',
                data: {
                    labels: [T('legCalls'), T('legChats'), T('legComplaints'), T('legSurveys')],
                    datasets: [{
                        data: [calls, chats, complaints, surveys],
                        backgroundColor: ['#4F8EF7', '#00D4AA', '#FF6B6B', '#F0A500'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    cutout: '68%'
                }
            });
            var legRows = [
                [T('legCalls'), '#4F8EF7', calls, Math.round(calls / tot * 100)],
                [T('legChats'), '#00D4AA', chats, Math.round(chats / tot * 100)],
                [T('legComplaints'), '#FF6B6B', complaints, Math.round(complaints / tot * 100)],
                [T('legSurveys'), '#F0A500', surveys, Math.round(surveys / tot * 100)]
            ];
            el('chMixLeg').innerHTML = legRows.map(function(r) {
                return '<div class="leg-item"><div class="leg-dot" style="background:' + r[1] +
                    '"></div><strong style="color:var(--text)">' + r[2].toLocaleString() + '</strong>&nbsp;' + r[
                    0] + '&nbsp;<span style="color:var(--text3)">' + r[3] + '%</span></div>';
            }).join('');
            var m6 = ML.slice(-6);
            ci.cvTrend = new Chart(el('cvTrend'), {
                type: 'line',
                data: {
                    labels: m6.map(function(m) {
                        return ml(m).slice(0, 3);
                    }),
                    datasets: [{
                            label: T('legCalls'),
                            data: m6.map(function(m) {
                                return parseFloat((state.data[m.key] || {}).c8 || 0) || Math.floor(900 +
                                    Math.random() * 400);
                            }),
                            borderColor: '#4F8EF7',
                            backgroundColor: 'rgba(79,142,247,0.08)',
                            borderWidth: 2,
                            tension: .4,
                            pointRadius: 3,
                            fill: true
                        },
                        {
                            label: T('legChats'),
                            data: m6.map(function(m) {
                                return parseFloat((state.data[m.key] || {}).c9 || 0) || Math.floor(
                                    4000 + Math.random() * 2000);
                            }),
                            borderColor: '#00D4AA',
                            backgroundColor: 'rgba(0,212,170,0.08)',
                            borderWidth: 2,
                            tension: .4,
                            pointRadius: 3,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: 'rgba(255,255,255,0.03)'
                            },
                            ticks: {
                                color: '#4A5A75',
                                font: {
                                    size: 10
                                }
                            }
                        },
                        y: {
                            grid: {
                                color: 'rgba(255,255,255,0.03)'
                            },
                            ticks: {
                                color: '#4A5A75',
                                font: {
                                    size: 10
                                }
                            }
                        }
                    }
                }
            });
            el('cvLeg').innerHTML = '<div class="leg-item"><div class="leg-dot" style="background:#4F8EF7"></div>' + T(
                'legCalls') + '</div><div class="leg-item"><div class="leg-dot" style="background:#00D4AA"></div>' + T(
                'legChats') + '</div>';
            var revD = m6.map(function(m) {
                return parseFloat((state.data[m.key] || {}).s1 || 0) || Math.floor(1400000 + Math.random() *
                600000);
            });
            var taxD = revD.map(function(v) {
                return Math.round(v * 0.15);
            });
            ci.revChart = new Chart(el('revChart'), {
                type: 'bar',
                data: {
                    labels: m6.map(function(m) {
                        return ml(m).slice(0, 3);
                    }),
                    datasets: [{
                            label: T('legNetRev'),
                            data: revD.map(function(v, i) {
                                return v - taxD[i];
                            }),
                            backgroundColor: 'rgba(0,212,170,0.7)',
                            borderRadius: 3,
                            stack: 'rev'
                        },
                        {
                            label: T('legTax'),
                            data: taxD,
                            backgroundColor: 'rgba(240,165,0,0.6)',
                            borderRadius: 3,
                            stack: 'rev'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            stacked: true,
                            ticks: {
                                color: '#4A5A75',
                                font: {
                                    size: 10
                                }
                            }
                        },
                        y: {
                            grid: {
                                color: 'rgba(255,255,255,0.03)'
                            },
                            stacked: true,
                            ticks: {
                                color: '#4A5A75',
                                font: {
                                    size: 10
                                },
                                callback: function(v) {
                                    return (v / 1000).toFixed(0) + 'K';
                                }
                            }
                        }
                    }
                }
            });
            el('revLeg').innerHTML = '<div class="leg-item"><div class="leg-dot" style="background:#00D4AA"></div>' + T(
                'legNetRev') + '</div><div class="leg-item"><div class="leg-dot" style="background:#F0A500"></div>' + T(
                'legTax') + '</div>';
            var teams = ['sales', 'cs', 'coord', 'training'];
            var avgP = teams.map(function(t) {
                var kpis = state.kpis.filter(function(k) {
                    return k.team === t;
                });
                var vals = kpis.map(function(k) {
                    return pctTgt(k, (md[k.id] || 0));
                });
                return vals.length ? Math.round(vals.reduce(function(a, b) {
                    return a + b;
                }, 0) / vals.length) : 0;
            });
            ci.teamRadar = new Chart(el('teamRadar'), {
                type: 'radar',
                data: {
                    labels: teams.map(tl),
                    datasets: [{
                        label: '%',
                        data: avgP,
                        borderColor: '#00D4AA',
                        backgroundColor: 'rgba(0,212,170,0.12)',
                        pointBackgroundColor: '#00D4AA',
                        borderWidth: 2,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        r: {
                            angleLines: {
                                color: 'rgba(255,255,255,0.05)'
                            },
                            grid: {
                                color: 'rgba(255,255,255,0.05)'
                            },
                            pointLabels: {
                                color: '#8B9BB4',
                                font: {
                                    size: 11
                                }
                            },
                            ticks: {
                                display: false
                            },
                            min: 0,
                            max: 100
                        }
                    }
                }
            });
        }

        function renderTeamSections(md) {
            var container = el('teamSections');
            container.innerHTML = '';
            var isAr = lang === 'ar';
            var dir = isAr ? 'row-reverse' : 'row';
            ['sales', 'cs', 'coord', 'training'].forEach(function(t) {
                if (!state.adminSettings.teamsVisible[t]) return;
                var kpis = state.kpis.filter(function(k) {
                    return k.team === t;
                }).sort(function(a, b) {
                    return a.order - b.order;
                });
                var filled = kpis.filter(function(k) {
                    return md[k.id] !== undefined && md[k.id] !== '';
                });
                var sec = document.createElement('div');
                sec.style.marginBottom = '24px';
                sec.innerHTML =
                    '<div class="sec-hdr"><div style="display:flex;align-items:center;gap:8px;flex-direction:' +
                    dir + '">' +
                    '<div style="width:3px;height:14px;border-radius:2px;background:' + tc(t) + '"></div>' +
                    '<div class="sec-title" style="color:' + tc(t) + '">' + tl(t) + '</div>' +
                    '<div style="font-size:10px;color:var(--text3)">' + filled.length + '/' + kpis.length +
                    '</div></div>' +
                    '<div class="sec-actions">' +
                    '<button class="btn btn-ghost" style="font-size:11px" onclick="selectTeam(\'' + t +
                    '\',el(\'nav-' + t + '\'))">' + T('viewAll') + '</button>' +
                    '<button class="btn btn-ghost" style="font-size:11px" onclick="addKpiModal(\'' + t +
                    '\')">+</button>' +
                    '</div></div>' +
                    '<div class="kpi-grid" id="og-' + t + '"></div>';
                container.appendChild(sec);
                var g = el('og-' + t);
                kpis.slice(0, 6).forEach(function(k) {
                    g.innerHTML += buildKpiCard(k, md[k.id]);
                });
                if (!kpis.length) g.innerHTML =
                    '<div style="font-size:11px;color:var(--text3);padding:8px;grid-column:1/-1">' + T('noKpis') +
                    '</div>';
            });
        }

        // ═══════════════════ TEAM PAGE ═══════════════════
        function renderTeamPage(main, team, meta) {
            var md = state.data[meta.key] || {};
            var kpis = state.kpis.filter(function(k) {
                return k.team === team;
            }).sort(function(a, b) {
                return a.order - b.order;
            });
            var color = tc(team);
            var rating = ((state.ratings[meta.key]) || {})[team] || 0;
            var isAr = lang === 'ar';
            var dir = isAr ? 'row-reverse' : 'row';
            var align = isAr ? 'right' : 'left';
            var filled = kpis.filter(function(k) {
                return md[k.id] !== undefined && md[k.id] !== '';
            }).length;
            var ratingHtml = state.adminSettings.enableRatings ? (
                '<div class="report-section" style="margin-bottom:16px">' +
                '<div style="display:flex;align-items:center;justify-content:space-between;flex-direction:' + dir +
                ';margin-bottom:8px">' +
                '<div style="font-size:13px;font-weight:600">' + T('pgRating') + '</div>' +
                '<div style="display:flex;gap:4px" id="starRating">' +
                [1, 2, 3, 4, 5].map(function(s) {
                    return '<span onclick="setRating(\'' + team + '\',' + s +
                        ')" style="font-size:22px;cursor:pointer;color:' + (rating >= s ? 'var(--gold)' :
                            'var(--text3)') + '">' + (rating >= s ? '★' : '☆') + '</span>';
                }).join('') +
                '</div></div>' +
                '<textarea id="teamNote" placeholder="' + T('pgNotes') +
                '" style="width:100%;height:72px;font-size:12px;resize:vertical;text-align:' + align + '">' + (state
                    .adminSettings.notesPerTeam[team] || '') + '</textarea>' +
                '<div style="display:flex;justify-content:flex-start;margin-top:8px;flex-direction:' + dir +
                '"><button class="btn btn-ghost btn-sm" onclick="saveNote(\'' + team + '\')">' + T('pgSaveNote') +
                '</button></div>' +
                '</div>'
            ) : '';
            main.innerHTML = '<div class="page-hdr">' +
                '<div class="page-hdr-left">' +
                '<div style="width:4px;height:20px;border-radius:2px;background:' + color + '"></div>' +
                '<div class="page-title">' + tl(team) + '</div>' +
                '<div class="month-chip">' + ml(meta) + '</div>' +
                '<span style="font-size:11px;color:var(--text3)">' + filled + '/' + kpis.length + '</span>' +
                '</div>' +
                '<div class="page-hdr-right">' +
                '<button class="btn btn-ghost" onclick="addKpiModal(\'' + team + '\')">+ ' + T('pgAddKpi').replace('+ ',
                '') + '</button>' +
                '<button class="btn btn-cyan" onclick="enterDataPrompt(\'' + team + '\')">' + T('pgEnterData') +
                '</button>' +
                '</div></div>' +
                ratingHtml +
                '<div class="sec-hdr"><div class="sec-title">' + T('pgKpi') + ' (' + kpis.length + ')</div></div>' +
                '<div class="kpi-grid" id="teamKpiGrid" style="margin-bottom:24px"></div>' +
                '<div class="charts-grid" id="teamChartsGrid" style="margin-bottom:20px"></div>' +
                '<div class="sec-hdr"><div class="sec-title">' + T('pgTable') + '</div></div>' +
                '<div class="tbl-wrap"><table class="data-tbl"><thead><tr>' +
                '<th>' + T('colKpi') + '</th><th>' + T('colActual') + '</th><th>' + T('colTarget') + '</th>' +
                '<th>' + T('colPct') + '</th><th>' + T('colStatus') + '</th><th>' + T('colDate') + '</th><th></th>' +
                '</tr></thead><tbody id="teamTbl"></tbody></table></div>';
            var g = el('teamKpiGrid');
            if (!kpis.length) {
                g.innerHTML =
                    '<div style="grid-column:1/-1"><div class="empty-state" style="text-align:center;padding:50px 24px;color:var(--text2)"><div style="font-size:36px;margin-bottom:12px">📋</div><h3 style="color:var(--text);margin-bottom:6px">' +
                    T('noKpisEmpty') + '</h3><p style="font-size:12px;margin-bottom:14px">' + T('addFirst') +
                    '</p><button class="btn btn-cyan" onclick="addKpiModal(\'' + team + '\')">' + T('btnAddKpi') +
                    '</button></div></div>';
            } else kpis.forEach(function(k) {
                g.innerHTML += buildKpiCard(k, md[k.id]);
            });
            var tbody = el('teamTbl');
            kpis.forEach(function(k) {
                var val = md[k.id];
                var p = val !== undefined && val !== '' ? ((parseFloat(val) / parseFloat(k.target)) * 100).toFixed(
                    1) : '—';
                var st = val !== undefined ? kpiSt(k, val) : {
                    cls: 'gs-monitor',
                    lbl: '—'
                };
                var uploadDate = getUploadDate(meta.key);
                tbody.innerHTML += '<tr>' +
                    '<td><div style="display:flex;align-items:center;gap:8px;flex-direction:' + dir +
                    '"><span style="font-size:14px">' + (k.icon || '📊') +
                    '</span><span style="font-weight:500;color:var(--text)">' + k.name + '</span></div></td>' +
                    '<td style="font-weight:600;color:' + color + '">' + fmt(val, k.unit) + '</td>' +
                    '<td style="color:var(--text3)">' + fmt(k.target, k.unit) + '</td>' +
                    '<td><div style="display:flex;align-items:center;gap:6px;flex-direction:' + dir +
                    '"><div style="width:50px;height:4px;background:var(--surface3);border-radius:2px;overflow:hidden"><div style="height:100%;width:' +
                    Math.min(parseFloat(p) || 0, 100) + '%;background:' + color +
                    ';border-radius:2px"></div></div><span style="font-size:10px;color:var(--text2)">' + p +
                    '%</span></div></td>' +
                    '<td><span class="gauge-status ' + st.cls + '" style="font-size:10px">● ' + st.lbl +
                    '</span></td>' +
                    '<td><span class="date-badge">' + (uploadDate || '—') + '</span></td>' +
                    '<td><div style="display:flex;gap:4px;flex-direction:' + dir + '">' +
                    '<button class="btn btn-ghost btn-xs" onclick="editKpiModal(\'' + k.id + '\')">✏</button>' +
                    '<button class="btn btn-ghost btn-xs" onclick="editValueModal(\'' + k.id + '\')">⌨</button>' +
                    '<button class="btn btn-danger btn-xs" onclick="deleteKpi(\'' + k.id + '\')">✕</button>' +
                    '</div></td></tr>';
            });
            renderTeamCharts(team, kpis, md, color);
        }

        function setRating(team, stars) {
            if (!state.ratings[state.activeMonth]) state.ratings[state.activeMonth] = {};
            state.ratings[state.activeMonth][team] = stars;
            saveState();
            renderMain();
        }

        function saveNote(team) {
            var note = (el('teamNote') || {}).value || '';
            state.adminSettings.notesPerTeam[team] = note;
            saveState();
            toast(T('noteSaved'));
        }

        function renderTeamCharts(team, kpis, md, color) {
            var g = el('teamChartsGrid');
            if (!g) return;
            var chartK = kpis.filter(function(k) {
                return md[k.id] !== undefined && md[k.id] !== '' && !isNaN(parseFloat(md[k.id]));
            });
            if (!chartK.length) {
                g.style.display = 'none';
                return;
            }
            g.innerHTML =
                '<div class="chart-card"><div class="sec-hdr" style="margin-bottom:10px"><div class="sec-title">' + T(
                    'pgVsTarget') + '</div></div>' +
                '<div class="chart-wrap" style="height:' + Math.max(200, chartK.length * 38 + 70) +
                'px"><canvas id="tc1" role="img" aria-label="' + T('pgVsTarget') + '"></canvas></div></div>' +
                '<div class="chart-card"><div class="sec-hdr" style="margin-bottom:10px"><div class="sec-title">' + T(
                    'pgDist') + '</div></div>' +
                '<div class="chart-wrap" style="height:200px"><canvas id="tc2" role="img" aria-label="' + T('pgDist') +
                '"></canvas></div>' +
                '<div class="chart-legend" id="tc2leg"></div></div>';
            var pcts = chartK.map(function(k) {
                return Math.round(Math.min(pctTgt(k, md[k.id]), 150));
            });
            ci.tc1 = new Chart(el('tc1'), {
                type: 'bar',
                data: {
                    labels: chartK.map(function(k) {
                        return k.name.length > 16 ? k.name.slice(0, 14) + '…' : k.name;
                    }),
                    datasets: [{
                        label: '%',
                        data: pcts,
                        backgroundColor: pcts.map(function(p) {
                            return p >= 90 ? '#4ADE80' : p >= 70 ? '#F0A500' : '#FF6B6B';
                        }),
                        borderRadius: 4
                    }, {
                        label: T('colTarget'),
                        data: chartK.map(function() {
                            return 100;
                        }),
                        backgroundColor: 'rgba(255,255,255,0.04)',
                        borderRadius: 4
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            min: 0,
                            max: 150,
                            grid: {
                                color: 'rgba(255,255,255,0.04)'
                            },
                            ticks: {
                                color: '#4A5A75',
                                font: {
                                    size: 10
                                },
                                callback: function(v) {
                                    return v + '%';
                                }
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#8B9BB4',
                                font: {
                                    size: 10
                                }
                            }
                        }
                    }
                }
            });
            var on = chartK.filter(function(k) {
                return pctTgt(k, md[k.id]) >= 90;
            }).length;
            var mon = chartK.filter(function(k) {
                var p = pctTgt(k, md[k.id]);
                return p >= 70 && p < 90;
            }).length;
            var risk = chartK.filter(function(k) {
                return pctTgt(k, md[k.id]) < 70;
            }).length;
            ci.tc2 = new Chart(el('tc2'), {
                type: 'doughnut',
                data: {
                    labels: [T('gsOn'), T('gsMon'), T('gsRisk')],
                    datasets: [{
                        data: [on || 0, mon || 0, risk || 0],
                        backgroundColor: ['#4ADE80', '#F0A500', '#FF6B6B'],
                        borderWidth: 0,
                        cutout: '70%'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
            el('tc2leg').innerHTML = [
                [T('gsOn'), '#4ADE80', on],
                [T('gsMon'), '#F0A500', mon],
                [T('gsRisk'), '#FF6B6B', risk]
            ].map(function(r) {
                return '<div class="leg-item"><div class="leg-dot" style="background:' + r[1] + '"></div>' + r[0] +
                    ': <strong style="color:var(--text)">' + r[2] + '</strong></div>';
            }).join('');
        }

        // ═══════════════════ TRENDS ═══════════════════
        function renderTrends(main, meta) {
            var hasData = ML.filter(function(m) {
                return state.data[m.key] && Object.keys(state.data[m.key]).length > 0;
            });
            main.innerHTML = '<div class="page-hdr"><div class="page-hdr-left"><div class="page-title">' + T('pgTrends') +
                '</div><div class="month-chip">' + T('allMonths') + '</div></div></div>' +
                (hasData.length < 2 ?
                    '<div style="text-align:center;padding:50px;color:var(--text2)"><div style="font-size:36px;margin-bottom:12px">📈</div><h3 style="color:var(--text)">' +
                    T('notEnough') + '</h3><p>' + T('notEnoughSub') + '</p></div>' : '') +
                '<div id="trendCharts"></div>';
            if (hasData.length < 2) return;
            var tc2 = el('trendCharts');
            var months = ML.filter(function(m) {
                return state.data[m.key] && Object.keys(state.data[m.key]).length > 0;
            });
            var palette = ['#00D4AA', '#F0A500', '#4F8EF7', '#A855F7', '#FB923C', '#F472B6', '#4ADE80', '#FF6B6B'];
            ['sales', 'cs', 'coord', 'training'].forEach(function(team) {
                var kpis = state.kpis.filter(function(k) {
                    return k.team === team;
                });
                var kwd = kpis.filter(function(k) {
                    return months.some(function(m) {
                        return (state.data[m.key] || {})[k.id] !== undefined;
                    });
                });
                if (!kwd.length) return;
                var cid = 'tr-' + team;
                var div = document.createElement('div');
                div.className = 'chart-card';
                div.style.marginBottom = '16px';
                div.innerHTML =
                    '<div class="sec-hdr" style="margin-bottom:10px"><div class="sec-title" style="color:' + tc(
                        team) + '">' + tl(team) + '</div></div>' +
                    '<div class="chart-legend" id="leg-' + team + '" style="margin-bottom:8px"></div>' +
                    '<div class="chart-wrap" style="height:220px"><canvas id="' + cid +
                    '" role="img" aria-label="' + tl(team) + '"></canvas></div>';
                tc2.appendChild(div);
                var ds = kwd.map(function(k, i) {
                    return {
                        label: k.name,
                        data: months.map(function(m) {
                            var v = (state.data[m.key] || {})[k.id];
                            return v !== undefined && v !== '' ? parseFloat(v) : null;
                        }),
                        borderColor: palette[i % palette.length],
                        backgroundColor: palette[i % palette.length] + '18',
                        borderWidth: 2,
                        pointRadius: 3,
                        tension: .35,
                        spanGaps: true
                    };
                });
                el('leg-' + team).innerHTML = ds.map(function(d) {
                    return '<div class="leg-item"><div class="leg-dot" style="background:' + d.borderColor +
                        '"></div>' + d.label + '</div>';
                }).join('');
                (function(cid, ds, months) {
                    ci[cid] = new Chart(el(cid), {
                        type: 'line',
                        data: {
                            labels: months.map(function(m) {
                                return ml(m).slice(0, 3) + ' ' + m.year;
                            }),
                            datasets: ds
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        color: 'rgba(255,255,255,0.03)'
                                    },
                                    ticks: {
                                        color: '#4A5A75',
                                        font: {
                                            size: 10
                                        }
                                    }
                                },
                                y: {
                                    grid: {
                                        color: 'rgba(255,255,255,0.03)'
                                    },
                                    ticks: {
                                        color: '#4A5A75',
                                        font: {
                                            size: 10
                                        }
                                    }
                                }
                            }
                        }
                    });
                })(cid, ds, months);
            });
        }

        // ═══════════════════ FORECAST ═══════════════════
        function renderForecast(main, meta) {
            var isAr = lang === 'ar';
            var dir = isAr ? 'row-reverse' : 'row';
            var align = isAr ? 'right' : 'left';
            main.innerHTML = '<div class="page-hdr"><div class="page-hdr-left"><div class="page-title">' + T('pgForecast') +
                '</div><div class="month-chip">' + T('next3') + '</div></div>' +
                '<div class="page-hdr-right"><button class="btn btn-ghost" onclick="renderForecast(el(\'mainArea\'),getMeta())">' +
                T('fcRefresh') + '</button></div></div>' +
                '<div style="background:rgba(79,142,247,0.08);border:1px solid rgba(79,142,247,0.2);border-radius:10px;padding:14px;margin-bottom:20px;text-align:' +
                align + '">' +
                '<div style="font-size:12px;color:var(--blue);font-weight:600;margin-bottom:4px">' + T('fcInfoTitle') +
                '</div>' +
                '<div style="font-size:11px;color:var(--text2)">' + T('fcInfoText') + '</div></div>' +
                '<div id="forecastContent"></div>';
            var fc = el('forecastContent');
            ['sales', 'cs', 'coord', 'training'].forEach(function(team) {
                var kpis = state.kpis.filter(function(k) {
                    return k.team === team;
                });
                var sec = document.createElement('div');
                sec.style.marginBottom = '24px';
                sec.innerHTML =
                    '<div class="sec-hdr"><div style="display:flex;align-items:center;gap:8px;flex-direction:' +
                    dir + '">' +
                    '<div style="width:3px;height:14px;border-radius:2px;background:' + tc(team) + '"></div>' +
                    '<div class="sec-title" style="color:' + tc(team) + '">' + tl(team) + '</div></div></div>' +
                    '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px;margin-bottom:12px" id="fc-' +
                    team + '"></div>' +
                    '<div class="chart-card"><div class="sec-hdr" style="margin-bottom:10px"><div class="sec-title">' +
                    T('fcNext3') + ' ' + tl(team) + '</div></div>' +
                    '<div class="chart-wrap" style="height:220px"><canvas id="fcChart-' + team +
                    '" role="img" aria-label="' + tl(team) + '"></canvas></div></div>';
                fc.appendChild(sec);
                var grid = el('fc-' + team);
                kpis.slice(0, 4).forEach(function(kpi) {
                    var forecast = forecastKpi(kpi.id, 3);
                    var vals = forecast.histVals || [];
                    var predLin = forecast.linear !== null ? fmt(Math.max(0, forecast.linear), kpi.unit) :
                        '—';
                    var predMA = forecast.ma !== null ? fmt(Math.max(0, forecast.ma), kpi.unit) : '—';
                    var trendIcon = forecast.trend === T('fcUp') ? '📈' : forecast.trend === T('fcDown') ?
                        '📉' : '➡️';
                    var badgeCls = forecast.trend === T('fcUp') ? 'fb-up' : forecast.trend === T('fcDown') ?
                        'fb-down' : 'fb-stable';
                    var growthColor = forecast.growth >= 0 ? '#4ADE80' : '#FF6B6B';
                    var growthStr = (forecast.growth >= 0 ? '+' : '') + parseFloat(forecast.growth).toFixed(
                        1) + '%';
                    grid.innerHTML += '<div class="forecast-card">' +
                        '<div style="display:flex;align-items:center;justify-content:space-between;flex-direction:' +
                        dir + ';margin-bottom:6px">' +
                        '<div style="font-size:13px;font-weight:600">' + kpi.name + '</div>' +
                        '<div style="font-size:18px">' + (kpi.icon || '📊') + '</div></div>' +
                        '<div class="forecast-badge ' + badgeCls + '" style="margin-bottom:8px">' +
                        trendIcon + ' ' + forecast.trend + '</div>' +
                        '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px">' +
                        '<div style="background:var(--surface2);border-radius:8px;padding:8px;text-align:' +
                        align + '">' +
                        '<div style="font-size:9px;color:var(--text3);margin-bottom:2px">' + T('fcLinear') +
                        '</div>' +
                        '<div style="font-size:16px;font-weight:700;color:var(--cyan)">' + predLin +
                        '</div></div>' +
                        '<div style="background:var(--surface2);border-radius:8px;padding:8px;text-align:' +
                        align + '">' +
                        '<div style="font-size:9px;color:var(--text3);margin-bottom:2px">' + T('fcMA') +
                        '</div>' +
                        '<div style="font-size:16px;font-weight:700;color:var(--gold)">' + predMA +
                        '</div></div></div>' +
                        '<div style="font-size:10px;color:var(--text3);text-align:' + align + '">' + T(
                            'fcGrowth') + ' <strong style="color:' + growthColor + '">' + growthStr +
                        '</strong> &nbsp;|&nbsp; ' + T('fcConf') + ' <strong style="color:var(--text2)">' +
                        forecast.confidence + '</strong></div>' +
                        '<div style="font-size:10px;color:var(--text3);text-align:' + align +
                        ';margin-top:4px">' + T('fcBasedOn') + ' <strong>' + vals.length + '</strong> ' + T(
                            'fcMonths') + '</div></div>';
                });
                (function(team, kpis) {
                    setTimeout(function() {
                        var canvas = el('fcChart-' + team);
                        if (!canvas) return;
                        var mainKpi = kpis[0];
                        if (!mainKpi) return;
                        var hist = getHistVals(mainKpi.id, 12);
                        if (!hist.length) return;
                        var histLabels = hist.map(function(h) {
                            return ml(h.month).slice(0, 3) + ' ' + h.month.year;
                        });
                        var histVals2 = hist.map(function(h) {
                            return h.val;
                        });
                        var futureMonths = [];
                        var curIdx = ML.findIndex(function(m) {
                            return m.key === state.activeMonth;
                        });
                        for (var i = 1; i <= 3; i++) {
                            if (curIdx + i < ML.length) futureMonths.push(ML[curIdx + i]);
                        }
                        var futureLinVals = futureMonths.map(function(_, i) {
                            var f = forecastKpi(mainKpi.id, i + 1);
                            return f.linear !== null ? Math.max(0, f.linear) : null;
                        });
                        var futureMAVals = futureMonths.map(function() {
                            var f = forecastKpi(mainKpi.id, 1);
                            return f.ma !== null ? Math.max(0, f.ma) : null;
                        });
                        var allLabels = histLabels.concat(futureMonths.map(function(m) {
                            return ml(m).slice(0, 3) + ' ' + m.year;
                        }));
                        var padHist = allLabels.map(function(_, i) {
                            return i < hist.length ? histVals2[i] : null;
                        });
                        var lastVal = hist[hist.length - 1].val;
                        var bridgeLin = [lastVal].concat(futureLinVals.filter(function(v) {
                            return v !== null;
                        }));
                        var bridgeMA = [lastVal].concat(futureMAVals.filter(function(v) {
                            return v !== null;
                        }));
                        var bridgeLabels = [histLabels[histLabels.length - 1]].concat(futureMonths.map(
                            function(m) {
                                return ml(m).slice(0, 3) + ' ' + m.year;
                            }));
                        ci['fcChart-' + team] = new Chart(canvas, {
                            type: 'line',
                            data: {
                                labels: allLabels,
                                datasets: [{
                                        label: T('fcActual'),
                                        data: padHist,
                                        borderColor: '#00D4AA',
                                        backgroundColor: 'rgba(0,212,170,0.1)',
                                        borderWidth: 2,
                                        pointRadius: 3,
                                        tension: .4,
                                        fill: true,
                                        spanGaps: false
                                    },
                                    {
                                        label: T('fcLinLbl'),
                                        data: histLabels.map(function() {
                                            return null;
                                        }).slice(0, -1).concat(bridgeLin),
                                        borderColor: '#4F8EF7',
                                        borderWidth: 2,
                                        borderDash: [6, 4],
                                        pointRadius: 4,
                                        tension: .3,
                                        fill: false,
                                        spanGaps: true
                                    },
                                    {
                                        label: T('fcMALbl'),
                                        data: histLabels.map(function() {
                                            return null;
                                        }).slice(0, -1).concat(bridgeMA),
                                        borderColor: '#F0A500',
                                        borderWidth: 2,
                                        borderDash: [4, 4],
                                        pointRadius: 4,
                                        tension: .3,
                                        fill: false,
                                        spanGaps: true
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            color: '#8B9BB4',
                                            font: {
                                                size: 10
                                            },
                                            boxWidth: 10
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        grid: {
                                            color: 'rgba(255,255,255,0.03)'
                                        },
                                        ticks: {
                                            color: '#4A5A75',
                                            font: {
                                                size: 9
                                            }
                                        }
                                    },
                                    y: {
                                        grid: {
                                            color: 'rgba(255,255,255,0.03)'
                                        },
                                        ticks: {
                                            color: '#4A5A75',
                                            font: {
                                                size: 10
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }, 80);
                })(team, kpis);
            });
        }

        // ═══════════════════ REPORTS ═══════════════════
        function renderReports(main, meta) {
            var md = state.data[meta.key] || {};
            var isAr = lang === 'ar';
            var align = isAr ? 'right' : 'left';
            var dir = isAr ? 'row-reverse' : 'row';
            var allKpis = state.kpis;
            var filled = allKpis.filter(function(k) {
                return md[k.id] !== undefined && md[k.id] !== '';
            });
            var onTarget = filled.filter(function(k) {
                return pctTgt(k, md[k.id]) >= 90;
            }).length;
            var totalRev = parseFloat(md.s1 || 0);
            var csat = parseFloat(md.c1 || 0);
            main.innerHTML = '<div class="page-hdr">' +
                '<div class="page-hdr-left"><div class="page-title">' + T('pgReports') + '</div><div class="month-chip">' +
                ml(meta) + '</div></div>' +
                '<div class="page-hdr-right">' +
                '<button class="btn btn-gold" onclick="generatePDF()">' + T('btnPDF') + '</button>' +
                '<button class="btn btn-ghost" onclick="exportCurrentMonth()">' + T('btnExcel') + '</button>' +
                '</div></div>' +
                '<div class="report-section">' +
                '<div style="font-size:13px;font-weight:700;margin-bottom:12px;text-align:' + align + '">' + T(
                    'rptSettings') + '</div>' +
                '<div style="display:flex;gap:14px;flex-wrap:wrap;flex-direction:' + dir + '">' +
                '<div class="fg"><label>' + T('rptType') + '</label><select style="width:180px"><option>' + T(
                'rptMonthly') + '</option><option>' + T('rptWeekly') + '</option><option>' + T('rptQuarterly') +
                '</option></select></div>' +
                '<div class="fg"><label>' + T('rptRefMonth') +
                '</label><select id="reportMonthSel" style="width:180px" onchange="state.activeMonth=this.value;renderSidebar();renderReports(el(\'mainArea\'),getMeta())">' +
                ML.map(function(m) {
                    return '<option value="' + m.key + '"' + (m.key === state.activeMonth ? ' selected' : '') + '>' +
                        ml(m) + '</option>';
                }).join('') +
                '</select></div></div></div>' +
                '<div id="reportPreview"></div>';
            renderReportPreview(md, meta);
        }

        function renderReportPreview(md, meta) {
            var allKpis = state.kpis;
            var filled = allKpis.filter(function(k) {
                return md[k.id] !== undefined && md[k.id] !== '';
            });
            var onTarget = filled.filter(function(k) {
                return pctTgt(k, md[k.id]) >= 90;
            }).length;
            var totalRev = parseFloat(md.s1 || 0);
            var csat = parseFloat(md.c1 || 0);
            var isAr = lang === 'ar';
            var align = isAr ? 'right' : 'left';
            var dir = isAr ? 'row-reverse' : 'row';
            var teamBlocks = '';
            ['sales', 'cs', 'coord', 'training'].filter(function(t) {
                return state.adminSettings.teamsVisible[t];
            }).forEach(function(team) {
                var kpis = state.kpis.filter(function(k) {
                    return k.team === team;
                }).sort(function(a, b) {
                    return a.order - b.order;
                });
                var filledK = kpis.filter(function(k) {
                    return md[k.id] !== undefined && md[k.id] !== '';
                });
                var avgPct = filledK.length ? Math.round(filledK.reduce(function(s, k) {
                    return s + pctTgt(k, md[k.id]);
                }, 0) / filledK.length) : 0;
                var status = avgPct >= 90 ? T('perfExc') : avgPct >= 75 ? T('perfGood') : avgPct >= 60 ? T(
                    'perfAcc') : T('perfNeed');
                var statusColor = avgPct >= 90 ? '#4ADE80' : avgPct >= 75 ? '#00D4AA' : avgPct >= 60 ? '#F0A500' :
                    '#FF6B6B';
                var rating = ((state.ratings[state.activeMonth]) || {})[team] || 0;
                var note = state.adminSettings.notesPerTeam[team] || T('noKpis');
                teamBlocks +=
                    '<div style="background:var(--surface2);border-radius:10px;padding:14px;margin-bottom:12px;border-' +
                    (isAr ? 'right' : 'left') + ':3px solid ' + tc(team) + '">' +
                    '<div style="display:flex;align-items:center;justify-content:space-between;flex-direction:' +
                    dir + ';margin-bottom:10px">' +
                    '<div style="display:flex;align-items:center;gap:8px;flex-direction:' + dir +
                    '"><div style="font-size:14px;font-weight:700;color:' + tc(team) + '">' + tl(team) + '</div>' +
                    (rating ? '<div style="color:var(--gold);font-size:12px">' + '★'.repeat(rating) + '☆'.repeat(5 -
                        rating) + '</div>' : '') + '</div>' +
                    '<div style="display:flex;align-items:center;gap:8px;flex-direction:' + dir +
                    '"><span style="font-size:11px;color:var(--text2)">' + filledK.length + '/' + kpis.length +
                    ' ' + T('rptKpis') + '</span>' +
                    '<span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;background:' +
                    statusColor + '22;color:' + statusColor + '">' + status + ' — ' + avgPct +
                    '%</span></div></div>' +
                    '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:8px;margin-bottom:10px">' +
                    kpis.slice(0, 4).map(function(k) {
                        return '<div style="background:var(--surface3);border-radius:8px;padding:8px;text-align:' +
                            align + '"><div style="font-size:10px;color:var(--text3);margin-bottom:2px">' + (k
                                .icon || '📊') + ' ' + k.name +
                            '</div><div style="font-size:14px;font-weight:700;color:' + tc(team) + '">' + fmt(
                                md[k.id], k.unit) + '</div><div style="font-size:10px;color:var(--text3)">' + T(
                                'colTarget') + ': ' + fmt(k.target, k.unit) + '</div></div>';
                    }).join('') +
                    '</div><div style="font-size:11px;color:var(--text2);background:var(--surface3);padding:8px 12px;border-radius:7px;text-align:' +
                    align + ';line-height:1.6"><strong style="color:var(--text)">' + T('rptOpsNotes') +
                    '</strong> ' + note + '</div></div>';
            });
            var recs = buildRecs(md, isAr, dir, align);
            var prev = el('reportPreview');
            prev.innerHTML = '<div class="report-section" id="printReport">' +
                '<div style="text-align:center;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--border)">' +
                '<img src="' + el('logoImg').src + '" alt="Miran" style="height:44px;width:auto;margin-bottom:8px"><br>' +
                '<div style="font-size:22px;font-weight:700;margin-bottom:4px">' + state.adminSettings.companyName +
                '</div>' +
                '<div style="font-size:15px;color:var(--cyan);font-weight:600">' + state.adminSettings.reportHeader +
                '</div>' +
                '<div style="font-size:12px;color:var(--text2);margin-top:4px">' + ml(meta) + ' | ' + (isAr ?
                    'تاريخ الإصدار:' : 'Issued:') + ' ' + new Date().toLocaleDateString(isAr ? 'ar-SA' : 'en-US') +
                '</div>' +
                '</div>' +
                '<div style="font-size:13px;font-weight:700;margin-bottom:14px;text-align:' + align + '">' + T('rptExec') +
                '</div>' +
                '<div class="grid-3" style="margin-bottom:20px">' +
                '<div class="stat-card"><div class="stat-lbl">' + T('rptTotalRev') +
                '</div><div class="stat-val" style="color:var(--gold)">' + fmtS(totalRev || 0, 'currency') +
                '</div></div>' +
                '<div class="stat-card"><div class="stat-lbl">' + T('gsOn') +
                '</div><div class="stat-val" style="color:var(--green)">' + onTarget + '/' + filled.length +
                '</div><div class="trend-arrow" style="color:var(--text2)">' + (isAr ? 'من أصل ' + allKpis.length +
                    ' مؤشر' : 'of ' + allKpis.length + ' KPIs') + '</div></div>' +
                '<div class="stat-card"><div class="stat-lbl">' + T('rptCsat') +
                '</div><div class="stat-val" style="color:var(--cyan)">' + (csat ? fmt(csat, 'percent') : '—') +
                '</div></div>' +
                '</div>' +
                '<div style="font-size:13px;font-weight:700;margin-bottom:14px;text-align:' + align + '">' + T(
                    'rptTeamPerf') + '</div>' +
                teamBlocks +
                '<div style="font-size:13px;font-weight:700;margin-bottom:12px;text-align:' + align + '">' + T(
                'rptWritten') + '</div>' +
                '<div style="background:var(--surface2);border-radius:10px;padding:16px;margin-bottom:14px">' +
                '<textarea id="reportAnalysis" placeholder="' + T('rptAnalPlh') +
                '" style="width:100%;min-height:120px;background:transparent;border:none;font-size:13px;line-height:1.7;color:var(--text);resize:vertical;text-align:' +
                align + '">' + generateAutoAnalysis(md, meta) + '</textarea></div>' +
                '<div style="font-size:13px;font-weight:700;margin-bottom:12px;text-align:' + align + '">' + T('rptRec') +
                '</div>' +
                '<div id="recommendationsBox">' + recs + '</div>' +
                '<div style="text-align:center;padding-top:16px;border-top:1px solid var(--border);font-size:11px;color:var(--text3);margin-top:16px">' +
                state.adminSettings.reportFooter + ' | Miran OPS v3.0 | ' + new Date().toLocaleString(isAr ? 'ar-SA' :
                    'en-US') + '</div>' +
                '</div>';
        }

        function buildRecs(md, isAr, dir, align) {
            var recs = [];
            var csat = parseFloat(md.c1 || 0),
                conv = parseFloat(md.s6 || 0),
                onTime = parseFloat(md.o2 || 0),
                comp = parseFloat(md.t4 || 0);
            if (csat > 0 && csat < 85) recs.push({
                icon: '🎧',
                team: T('oCS'),
                text: T('recCsat'),
                prio: T('prioHigh'),
                color: '#FF6B6B'
            });
            if (conv > 0 && conv < 25) recs.push({
                icon: '💰',
                team: T('oSales'),
                text: T('recConv'),
                prio: T('prioMed'),
                color: '#F0A500'
            });
            if (onTime > 0 && onTime < 90) recs.push({
                icon: '📦',
                team: T('oCoord'),
                text: T('recOnTime'),
                prio: T('prioHigh'),
                color: '#FF6B6B'
            });
            if (comp > 0 && comp < 80) recs.push({
                icon: '🎓',
                team: T('oTraining'),
                text: T('recTraining'),
                prio: T('prioLow'),
                color: '#4ADE80'
            });
            if (!recs.length) recs.push({
                icon: '✅',
                team: T('recAll'),
                text: T('recAllGood'),
                prio: T('prioExc'),
                color: '#4ADE80'
            });
            return recs.map(function(r) {
                return '<div style="background:var(--surface2);border-radius:9px;padding:12px 14px;margin-bottom:8px;border-' +
                    (isAr ? 'right' : 'left') + ':3px solid ' + r.color +
                    ';display:flex;align-items:flex-start;justify-content:space-between;flex-direction:' + dir +
                    ';gap:12px">' +
                    '<div style="flex:1;text-align:' + align +
                    '"><div style="font-size:12px;font-weight:600;color:var(--text);margin-bottom:4px">' + r.icon +
                    ' ' + r.team + '</div><div style="font-size:12px;color:var(--text2);line-height:1.6">' + r
                    .text + '</div></div>' +
                    '<span style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;background:' + r
                    .color + '22;color:' + r.color + ';flex-shrink:0">' + r.prio + '</span></div>';
            }).join('');
        }

        function generateAutoAnalysis(md, meta) {
            var isAr = lang === 'ar';
            var rev = parseFloat(md.s1 || 0),
                csat = parseFloat(md.c1 || 0),
                onTime = parseFloat(md.o2 || 0),
                comp = parseFloat(md.t4 || 0),
                clients = parseFloat(md.s2 || 0);
            if (isAr) {
                return 'تقرير الأداء التشغيلي — ' + ml(meta) + '\n\n' +
                    'على صعيد المبيعات، ' + (rev > 0 ? 'بلغت إجمالي الإيرادات ' + fmt(rev, 'currency') +
                        ' خلال هذا الشهر، مع استقطاب ' + (clients || 'عدد من') + ' العملاء الجدد.' :
                        'لم يتم إدخال بيانات المبيعات بعد.') + '\n\n' +
                    'أما خدمة العملاء، ' + (csat > 0 ? 'فقد سجّل مؤشر رضا العملاء ' + fmt(csat, 'percent') + ', ' + (csat >=
                        90 ? 'وهو مستوى ممتاز.' : csat >= 75 ? 'وهو مستوى جيد مع وجود فرص للتحسين.' :
                        'مما يستدعي مراجعة أسباب تراجع الرضا.') : 'لم يتم إدخال بيانات خدمة العملاء.') + '\n\n' +
                    'فيما يخص قسم التنسيق، ' + (onTime > 0 ? 'بلغ معدل التسليم في الوقت ' + fmt(onTime, 'percent') + ', ' +
                        (onTime >= 95 ? 'وهو أداء متميز.' : 'وهو دون المستهدف.') : 'لم يتم إدخال بيانات التنسيق.') +
                    '\n\n' +
                    'وعلى صعيد التدريب، ' + (comp > 0 ? 'بلغت نسبة إكمال التدريب ' + fmt(comp, 'percent') + ', ' + (comp >=
                        90 ? 'وهو مستوى احترافي.' : 'ويمكن تعزيزها.') : 'لم يتم إدخال بيانات التدريب.');
            }
            return 'Operations Performance Report — ' + ml(meta) + '\n\n' +
                'Sales: ' + (rev > 0 ? 'Total revenue reached ' + fmt(rev, 'currency') + ' this month, with ' + (clients ||
                    'a number of') + ' new clients.' : 'Sales data not entered yet.') + '\n\n' +
                'Customer Service: ' + (csat > 0 ? 'CSAT score recorded ' + fmt(csat, 'percent') + ', ' + (csat >= 90 ?
                    'an excellent level.' : csat >= 75 ? 'a good level with room for improvement.' :
                    'which requires attention.') : 'CS data not entered.') + '\n\n' +
                'Coordination: ' + (onTime > 0 ? 'On-time delivery reached ' + fmt(onTime, 'percent') + ', ' + (onTime >=
                    95 ? 'outstanding performance.' : 'below target.') : 'Coordination data not entered.') + '\n\n' +
                'Training: ' + (comp > 0 ? 'Training completion reached ' + fmt(comp, 'percent') + ', ' + (comp >= 90 ?
                    'a professional level.' : 'can be improved.') : 'Training data not entered.');
        }

        function generatePDF() {
            var analysis = (el('reportAnalysis') || {}).value || generateAutoAnalysis(state.data[state.activeMonth] || {},
                getMeta());
            var isAr = lang === 'ar';
            var meta = getMeta();
            var md = state.data[state.activeMonth] || {};
            var win = window.open('', '_blank');
            if (!win) {
                toast(T('uploadFirst'));
                return;
            }
            var teamBlocks = '';
            ['sales', 'cs', 'coord', 'training'].filter(function(t) {
                return state.adminSettings.teamsVisible[t];
            }).forEach(function(team) {
                var kpis = state.kpis.filter(function(k) {
                    return k.team === team;
                });
                var filledK = kpis.filter(function(k) {
                    return md[k.id] !== undefined && md[k.id] !== '';
                });
                var avgPct = filledK.length ? Math.round(filledK.reduce(function(s, k) {
                    return s + pctTgt(k, md[k.id]);
                }, 0) / filledK.length) : 0;
                teamBlocks += '<div class="team-block" style="border-' + (isAr ? 'right' : 'left') + '-color:' + tc(
                        team) + ';">' +
                    '<div style="font-size:13px;font-weight:700;color:' + tc(team) + '">' + tl(team) + ' — ' +
                    avgPct + '%</div>' +
                    kpis.map(function(k) {
                        return '<span class="kpi-mini">' + (k.icon || '📊') + ' ' + k.name + ': <strong>' + fmt(
                            md[k.id], k.unit) + '</strong></span>';
                    }).join('') +
                    '<div class="note-box">' + T('rptOpsNotes') + ' ' + (state.adminSettings.notesPerTeam[team] ||
                        '—') + '</div></div>';
            });
            win.document.write('<!DOCTYPE html><html dir="' + (isAr ? 'rtl' : 'ltr') + '" lang="' + lang +
                '"><head><meta charset="UTF-8">' +
                '<style>@import url(\'https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&family=Inter:wght@400;500;700&display=swap\');' +
                'body{font-family:' + (isAr ? '\'Tajawal\',sans-serif' : '\'Inter\',sans-serif') +
                ';color:#111;background:#fff;padding:30px;font-size:13px;direction:' + (isAr ? 'rtl' : 'ltr') + '}' +
                'h2{font-size:15px;font-weight:700;border-bottom:2px solid #00D4AA;padding-bottom:6px;margin:18px 0 10px}' +
                '.header{text-align:center;border-bottom:3px solid #00D4AA;padding-bottom:16px;margin-bottom:20px}' +
                '.brand{font-size:22px;font-weight:700}.subtitle{font-size:14px;color:#00D4AA;margin-top:4px}' +
                '.stat-row{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:18px}' +
                '.stat-box{background:#f8f9fa;border-radius:8px;padding:12px;text-align:center;border:1px solid #e0e0e0}' +
                '.stat-val{font-size:20px;font-weight:700}.stat-lbl{font-size:11px;color:#888;margin-top:3px}' +
                '.team-block{background:#f8f9fa;border-radius:8px;padding:14px;margin-bottom:12px;border-' + (isAr ?
                    'right' : 'left') + ':4px solid #00D4AA}' +
                '.kpi-mini{display:inline-block;background:#fff;border:1px solid #e0e0e0;border-radius:6px;padding:5px 9px;margin:3px;font-size:11px}' +
                '.note-box{background:#fff;border:1px solid #ddd;border-radius:6px;padding:8px;margin-top:8px;font-size:12px;line-height:1.6}' +
                '.analysis-box{background:#f8f9fa;border-radius:8px;padding:14px;white-space:pre-wrap;font-size:13px;line-height:1.8}' +
                '.footer{text-align:center;font-size:11px;color:#888;margin-top:24px;border-top:1px solid #ddd;padding-top:12px}' +
                '@media print{body{padding:15px}}' +
                '</style></head><body>' +
                '<div class="header"><img src="' + el('logoImg').src +
                '" alt="Miran" style="height:50px;width:auto;margin-bottom:8px"><br>' +
                '<div class="brand">' + state.adminSettings.companyName + '</div>' +
                '<div class="subtitle">' + state.adminSettings.reportHeader + '</div>' +
                '<div style="font-size:12px;color:#888;margin-top:4px">' + ml(meta) + ' | ' + (isAr ? 'تاريخ الإصدار:' :
                    'Issued:') + ' ' + new Date().toLocaleDateString(isAr ? 'ar-SA' : 'en-US') + '</div></div>' +
                '<h2>' + T('rptExec') + '</h2>' +
                '<div class="stat-row">' +
                '<div class="stat-box"><div class="stat-lbl">' + T('rptTotalRev') +
                '</div><div class="stat-val" style="color:#F0A500">' + fmtS(parseFloat(md.s1 || 0), 'currency') +
                '</div></div>' +
                '<div class="stat-box"><div class="stat-lbl">' + T('rptCsat') +
                '</div><div class="stat-val" style="color:#00D4AA">' + (md.c1 ? fmt(md.c1, 'percent') : '—') +
                '</div></div>' +
                '<div class="stat-box"><div class="stat-lbl">' + T('rptUpload') +
                '</div><div class="stat-val" style="font-size:13px">' + (getUploadDate(state.activeMonth) || '—') +
                '</div></div>' +
                '</div>' +
                '<h2>' + T('rptTeamPerf') + '</h2>' + teamBlocks +
                '<h2>' + T('rptWritten') + '</h2><div class="analysis-box">' + analysis + '</div>' +
                '<div class="footer">' + state.adminSettings.reportFooter + ' | Miran OPS Performance v3.0</div>' +
                '</body></html>');
            win.document.close();
            setTimeout(function() {
                win.print();
            }, 800);
        }

        // ═══════════════════ ADMIN ═══════════════════
        function renderAdmin(main) {
            var s = state.adminSettings;
            var isAr = lang === 'ar';
            var align = isAr ? 'right' : 'left';
            var dir = isAr ? 'row-reverse' : 'row';
            main.innerHTML = '<div class="page-hdr">' +
                '<div class="page-hdr-left"><div style="width:4px;height:20px;border-radius:2px;background:var(--purple)"></div><div class="page-title">' +
                T('pgAdmin') + '</div><span class="tag tag-purple">' + T('adminSpecial') + '</span></div>' +
                '<div class="page-hdr-right"><button class="btn btn-purple" onclick="saveAdminSettings()">' + T(
                    'btnSaveAdmin') + '</button></div></div>' +
                '<div class="admin-card highlighted">' +
                '<div class="admin-section-title" style="flex-direction:' + dir + '">' + T('adminIdentity') + '</div>' +
                '<div class="form-grid">' +
                '<div class="fg"><label>' + T('lCompany') + '</label><input id="ad_cn" value="' + s.companyName +
                '"></div>' +
                '<div class="fg"><label>' + T('lRptHeader') + '</label><input id="ad_rh" value="' + s.reportHeader +
                '"></div>' +
                '<div class="fg full"><label>' + T('lRptFooter') + '</label><input id="ad_rf" value="' + s.reportFooter +
                '"></div>' +
                '<div class="fg"><label>' + T('lCurrSym') + '</label><input id="ad_cs" value="' + s.currencySymbol +
                '" style="width:120px"></div>' +
                '</div></div>' +
                '<div class="admin-card">' +
                '<div class="admin-section-title" style="flex-direction:' + dir + '">' + T('adminFeatures') + '</div>' +
                '<div style="display:flex;gap:16px;flex-wrap:wrap;flex-direction:' + dir + ';margin-bottom:12px">' +
                '<label style="display:flex;align-items:center;gap:8px;font-size:13px"><input type="checkbox" id="ad_fc" ' +
                (s.enableForecasting ? 'checked' : '') + '>' + T('featFc') + '</label>' +
                '<label style="display:flex;align-items:center;gap:8px;font-size:13px"><input type="checkbox" id="ad_rt" ' +
                (s.enableRatings ? 'checked' : '') + '>' + T('featRating') + '</label>' +
                '</div>' +
                '<div style="font-size:12px;font-weight:600;color:var(--text2);margin-bottom:8px;text-align:' + align +
                '">' + T('teamsVisLbl') + '</div>' +
                '<div style="display:flex;gap:10px;flex-wrap:wrap;flex-direction:' + dir + '">' +
                ['sales', 'cs', 'coord', 'training'].map(function(t) {
                    return '<label style="display:flex;align-items:center;gap:6px;font-size:13px;background:var(--surface2);padding:6px 12px;border-radius:8px"><input type="checkbox" id="ad_v_' +
                        t + '" ' + (s.teamsVisible[t] ? 'checked' : '') + '>' + tl(t) + '</label>';
                }).join('') +
                '</div></div>' +
                '<div class="admin-card">' +
                '<div class="admin-section-title" style="flex-direction:' + dir + '">' + T('adminNotes') + '</div>' +
                '<div class="form-grid">' +
                ['sales', 'cs', 'coord', 'training'].map(function(t) {
                    return '<div class="fg"><label style="color:' + tc(t) + '">' + tl(t) +
                        '</label><textarea id="ad_n_' + t + '" style="height:70px;resize:vertical">' + (s.notesPerTeam[
                            t] || '') + '</textarea></div>';
                }).join('') +
                '</div></div>' +
                '<div class="admin-card">' +
                '<div class="admin-section-title" style="justify-content:space-between;flex-direction:' + dir + '"><span>' +
                T('adminLog') + '</span>' +
                '<button class="btn btn-danger btn-xs" onclick="if(confirm(T(\'confirmClearLog\'))){state.uploadLog=[];saveState();renderAdmin(el(\'mainArea\'));}">' +
                T('btnClearLog') + '</button></div>' +
                '<div class="tbl-wrap" style="margin-bottom:0"><table class="data-tbl"><thead><tr>' +
                '<th>' + T('colMonth') + '</th><th>' + T('colUpDate') + '</th><th>' + T('colKpiCount') + '</th><th>' + T(
                    'colStatusH') + '</th>' +
                '</tr></thead><tbody>' +
                (state.uploadLog.length ? state.uploadLog.map(function(u) {
                    var m = ML.find(function(x) {
                        return x.key === u.monthKey;
                    });
                    return '<tr><td>' + (m ? ml(m) : u.monthKey) + '</td><td><span class="date-badge">' + u.date +
                        '</span></td><td>' + u.count + '</td><td><span class="gauge-status gs-on">' + T(
                        'uploadOk') + '</span></td></tr>';
                }).join('') : '<tr><td colspan="4" style="text-align:center;color:var(--text3);padding:16px">' + T(
                    'noUploads') + '</td></tr>') +
                '</tbody></table></div></div>' +
                '<div class="admin-card">' +
                '<div class="admin-section-title" style="justify-content:space-between;flex-direction:' + dir + '"><span>' +
                T('adminKpiMgmt') + ' (' + state.kpis.length + ')</span>' +
                '<button class="btn btn-cyan btn-sm" onclick="addKpiModal()">' + T('btnAddKpiAdmin') + '</button></div>' +
                '<div class="tbl-wrap" style="margin-bottom:0"><table class="data-tbl"><thead><tr>' +
                '<th>' + T('colKpi') + '</th><th>' + (isAr ? 'القسم' : 'Team') + '</th><th>' + T('colUnit') + '</th><th>' +
                T('colTarget') + '</th><th>' + T('colHB') + '</th><th>' + T('colActions') + '</th>' +
                '</tr></thead><tbody>' +
                state.kpis.map(function(k) {
                    return '<tr><td>' + (k.icon || '📊') + ' ' + k.name + '</td><td style="color:' + tc(k.team) + '">' +
                        tl(k.team) + '</td><td>' + k.unit + '</td><td>' + fmt(k.target, k.unit) + '</td><td>' + (k
                            .higherBetter ? '✅' : '❌') + '</td>' +
                        '<td><div style="display:flex;gap:4px;flex-direction:' + dir +
                        '"><button class="btn btn-ghost btn-xs" onclick="editKpiModal(\'' + k.id +
                        '\')">✏</button><button class="btn btn-danger btn-xs" onclick="deleteKpi(\'' + k.id +
                        '\')">✕</button></div></td></tr>';
                }).join('') +
                '</tbody></table></div></div>' +
                '<div class="admin-card" style="border-color:rgba(255,107,107,0.3)">' +
                '<div class="admin-section-title" style="color:var(--red);flex-direction:' + dir + '">' + T('adminDanger') +
                '</div>' +
                '<div style="display:flex;gap:10px;flex-direction:' + dir + ';flex-wrap:wrap">' +
                '<button class="btn btn-danger" onclick="if(confirm(T(\'confirmClear\'))){state.data={};saveState();renderMain();toast(T(\'clearedMsg\'));}">' +
                T('btnClearData') + '</button>' +
                '<button class="btn btn-danger" onclick="if(confirm(T(\'confirmReset\'))){state.kpis=DEFAULT_KPIS();saveState();renderSidebar();renderAdmin(el(\'mainArea\'));toast(T(\'resetMsg\'));}">' +
                T('btnResetKpis') + '</button>' +
                '</div></div>';
        }

        function saveAdminSettings() {
            var s = state.adminSettings;
            var g = function(id) {
                var e = el(id);
                return e ? e.value : s[id] || '';
            };
            s.companyName = g('ad_cn');
            s.reportHeader = g('ad_rh');
            s.reportFooter = g('ad_rf');
            s.currencySymbol = g('ad_cs');
            s.enableForecasting = (el('ad_fc') || {}).checked !== false;
            s.enableRatings = (el('ad_rt') || {}).checked !== false;
            ['sales', 'cs', 'coord', 'training'].forEach(function(t) {
                s.teamsVisible[t] = (el('ad_v_' + t) || {}).checked !== false;
                s.notesPerTeam[t] = (el('ad_n_' + t) || {}).value || '';
            });
            saveState();
            toast(T('adminSaved'));
        }

        // ═══════════════════ KPI CARD ═══════════════════
        function buildKpiCard(kpi, value) {
            var has = value !== undefined && value !== null && value !== '';
            var color = tc(kpi.team);
            var delta = has ? getDelta(kpi, value) : null;
            var isAr = lang === 'ar';
            var dir = isAr ? 'row-reverse' : 'row';
            var align = isAr ? 'right' : 'left';
            var badge = '';
            if (delta) {
                var good = kpi.higherBetter ? delta.up : !delta.up;
                badge = '<div class="kpi-badge ' + (good ? 'kb-up' : 'kb-down') + '">' + (delta.up ? '↑' : '↓') + Math.abs(
                    delta.pct) + '%</div>';
            } else if (has) badge = '<div class="kpi-badge kb-flat">—</div>';
            var pct = has ? pctTgt(kpi, value) : 0;
            return '<div class="kpi-card" draggable="true" data-id="' + kpi.id + '" ondragstart="dStart(event,\'' + kpi.id +
                '\')" ondragover="dOver(event)" ondrop="dDrop(event,\'' + kpi.id + '\')" ondragend="dEnd(event)">' +
                '<div class="kpi-actions"><button class="btn btn-ghost btn-xs" onclick="editKpiModal(\'' + kpi.id +
                '\')">✏</button><button class="btn btn-ghost btn-xs" onclick="editValueModal(\'' + kpi.id +
                '\')">⌨</button><button class="btn btn-danger btn-xs" onclick="deleteKpi(\'' + kpi.id +
                '\')">✕</button></div>' +
                '<div class="kpi-card-top"><div class="kpi-icon" style="background:' + tcd(kpi.team) + '">' + (kpi.icon ||
                    '📊') + '</div>' + badge + '</div>' +
                '<div class="kpi-label">' + kpi.name + '</div>' +
                '<div class="kpi-value" style="color:' + color + '">' + (has ? fmtS(value, kpi.unit) : '—') + '</div>' +
                (kpi.target && has ? '<div class="kpi-vs">↑ ' + Math.round(pct) + '% ' + T('pctOf') + '</div>' : '') +
                '<div class="kpi-prog"><div class="kpi-prog-bar" style="width:' + pct + '%;background:' + color +
                '"></div></div>' +
                '</div>';
        }

        // ═══════════════════ DRAG ═══════════════════
        function dStart(e, id) {
            dragSrcId = id;
            e.target.classList.add('dragging');
        }

        function dOver(e) {
            e.preventDefault();
        }

        function dEnd(e) {
            e.target.classList.remove('dragging');
        }

        function dDrop(e, tid) {
            e.preventDefault();
            if (dragSrcId === tid) return;
            var src = state.kpis.find(function(k) {
                    return k.id === dragSrcId;
                }),
                tgt = state.kpis.find(function(k) {
                    return k.id === tid;
                });
            if (!src || !tgt || src.team !== tgt.team) return;
            var tmp = src.order;
            src.order = tgt.order;
            tgt.order = tmp;
            saveState();
            renderMain();
        }

        // ═══════════════════ KPI MODAL ═══════════════════
        function addKpiModal(team) {
            editingKpiId = null;
            setTxt('kpiModalTitle', T('addKpiTitle'));
            ['kpiName', 'kpiTarget', 'kpiIcon'].forEach(function(id) {
                el(id).value = '';
            });
            el('kpiTeam').value = team || 'sales';
            el('kpiUnit').value = 'number';
            el('kpiHigherBetter').value = 'yes';
            el('kpiModal').classList.add('open');
        }

        function editKpiModal(id) {
            var k = state.kpis.find(function(x) {
                return x.id === id;
            });
            if (!k) return;
            editingKpiId = id;
            setTxt('kpiModalTitle', T('editKpiTitle'));
            el('kpiName').value = k.name;
            el('kpiTeam').value = k.team;
            el('kpiUnit').value = k.unit;
            el('kpiTarget').value = k.target || '';
            el('kpiHigherBetter').value = k.higherBetter ? 'yes' : 'no';
            el('kpiIcon').value = k.icon || '';
            el('kpiModal').classList.add('open');
        }

        function closeKpiModal() {
            el('kpiModal').classList.remove('open');
        }

        function saveKpi() {
            var name = el('kpiName').value.trim();
            if (!name) return toast(T('enterName'));
            if (editingKpiId) {
                var k = state.kpis.find(function(x) {
                    return x.id === editingKpiId;
                });
                k.name = name;
                k.team = el('kpiTeam').value;
                k.unit = el('kpiUnit').value;
                k.target = parseFloat(el('kpiTarget').value) || 0;
                k.higherBetter = el('kpiHigherBetter').value === 'yes';
                k.icon = el('kpiIcon').value || '📊';
            } else {
                var team = el('kpiTeam').value;
                var maxO = Math.max.apply(null, [0].concat(state.kpis.filter(function(k) {
                    return k.team === team;
                }).map(function(k) {
                    return k.order;
                }))) + 1;
                state.kpis.push({
                    id: 'k' + Date.now(),
                    name: name,
                    team: team,
                    unit: el('kpiUnit').value,
                    target: parseFloat(el('kpiTarget').value) || 0,
                    higherBetter: el('kpiHigherBetter').value === 'yes',
                    icon: el('kpiIcon').value || '📊',
                    order: maxO
                });
            }
            saveState();
            closeKpiModal();
            renderSidebar();
            renderMain();
            toast(editingKpiId ? T('kpiUpdated') : T('kpiAdded'));
        }

        function deleteKpi(id) {
            if (!confirm(T('kpiDeleted') + '?')) return;
            state.kpis = state.kpis.filter(function(k) {
                return k.id !== id;
            });
            Object.keys(state.data).forEach(function(m) {
                if (state.data[m]) delete state.data[m][id];
            });
            saveState();
            renderSidebar();
            renderMain();
            toast(T('kpiDeleted'));
        }

        function editValueModal(kpiId) {
            var kpi = state.kpis.find(function(k) {
                return k.id === kpiId;
            });
            if (!kpi) return;
            var meta = getMeta();
            var cur = (state.data[meta.key] || {})[kpiId] || '';
            var val = prompt('[' + tl(kpi.team) + '] ' + kpi.name + '\n' + T('promptTarget') + ' ' + fmt(kpi.target, kpi
                .unit) + '\n' + T('promptMonth') + ' ' + ml(meta) + '\n' + T('promptCurrent') + ' ' + (cur !== '' ?
                fmt(cur, kpi.unit) : T('promptEmpty')), cur);
            if (val === null) return;
            if (!state.data[meta.key]) state.data[meta.key] = {};
            state.data[meta.key][kpiId] = val === '' ? undefined : val;
            saveState();
            renderSidebar();
            renderMain();
            toast(T('kpiSaved'));
        }

        function enterDataPrompt(team) {
            var meta = getMeta();
            var kpis = state.kpis.filter(function(k) {
                return k.team === team;
            }).sort(function(a, b) {
                return a.order - b.order;
            });
            if (!kpis.length) return toast(T('noKpisDef'));
            var changed = false;
            for (var i = 0; i < kpis.length; i++) {
                var k = kpis[i];
                var cur = (state.data[meta.key] || {})[k.id] || '';
                var val = prompt('[' + tl(team) + '] ' + k.name + '\n' + T('promptTarget') + ' ' + fmt(k.target, k.unit) +
                    '\n' + T('promptCurrent') + ' ' + (cur !== '' ? fmt(cur, k.unit) : T('promptEmpty')), cur);
                if (val === null) break;
                if (!state.data[meta.key]) state.data[meta.key] = {};
                state.data[meta.key][k.id] = val === '' ? undefined : val;
                changed = true;
            }
            if (changed) {
                saveState();
                renderSidebar();
                renderMain();
                toast(T('dataSaved'));
            }
        }

        // ═══════════════════ IMPORT ═══════════════════
        function populateImportSelect() {
            var sel = el('importMonth');
            if (!sel) return;
            sel.innerHTML = '';
            ML.forEach(function(m) {
                var opt = document.createElement('option');
                opt.value = m.key;
                opt.textContent = ml(m);
                if (m.key === state.activeMonth) opt.selected = true;
                sel.appendChild(opt);
            });
        }

        function openImportModal() {
            populateImportSelect();
            pendingImportData = null;
            el('importPreview').style.display = 'none';
            el('confirmImportBtn').style.display = 'none';
            el('fileInput').value = '';
            el('importModal').classList.add('open');
        }

        function closeImportModal() {
            el('importModal').classList.remove('open');
        }

        function handleDragOver(e) {
            e.preventDefault();
            el('importZone').classList.add('drag-over');
        }

        function handleDragLeave() {
            el('importZone').classList.remove('drag-over');
        }

        function handleDrop(e) {
            e.preventDefault();
            el('importZone').classList.remove('drag-over');
            var f = e.dataTransfer.files[0];
            if (f) processFile(f);
        }

        function handleFileSelect(e) {
            var f = e.target.files[0];
            if (f) processFile(f);
        }

        function processFile(file) {
            var r = new FileReader();
            r.onload = function(e) {
                try {
                    var wb = XLSX.read(e.target.result, {
                        type: 'array'
                    });
                    var ws = wb.Sheets[wb.SheetNames[0]];
                    var rows = XLSX.utils.sheet_to_json(ws, {
                        header: 1,
                        defval: ''
                    });
                    if (rows.length < 2) return toast(lang === 'ar' ? 'الملف يبدو فارغاً' : 'File appears empty');
                    renderPreview(rows);
                    pendingImportData = rows;
                    el('confirmImportBtn').style.display = 'block';
                    setTxt('confirmImportBtn', T('btnImport'));
                } catch (err) {
                    toast('Error: ' + err.message);
                }
            };
            r.readAsArrayBuffer(file);
        }

        function renderPreview(rows) {
            var p = el('importPreview');
            var t = el('previewTable');
            var h = rows[0];
            var html = '<thead><tr>' + h.map(function(c) {
                return '<th>' + c + '</th>';
            }).join('') + '</tr></thead><tbody>';
            rows.slice(1, 7).forEach(function(row) {
                html += '<tr>' + row.map(function(c) {
                    return '<td>' + c + '</td>';
                }).join('') + '</tr>';
            });
            if (rows.length > 7) html += '<tr><td colspan="' + h.length + '" style="color:var(--text3)">... ' + (rows
                .length - 7) + (lang === 'ar' ? ' صف إضافي' : ' more rows') + '</td></tr>';
            t.innerHTML = html + '</tbody>';
            p.style.display = 'block';
        }

        function confirmImport() {
            if (!pendingImportData) return;
            var mk = el('importMonth').value;
            var rows = pendingImportData;
            var headers = rows[0].map(function(h) {
                return String(h).trim().toLowerCase();
            });
            var kc = headers.findIndex(function(h) {
                return h.includes('kpi') || h.includes('name') || h.includes('اسم') || h.includes('مؤشر');
            });
            var vc = headers.findIndex(function(h) {
                return h.includes('value') || h.includes('actual') || h.includes('قيمة') || h.includes('فعلي');
            });
            var md = state.data[mk] || {};
            var matched = 0;
            if (kc !== -1 && vc !== -1) {
                rows.slice(1).forEach(function(row) {
                    var name = String(row[kc] || '').trim().toLowerCase();
                    var val = row[vc];
                    if (!name || val === '') return;
                    var k = state.kpis.find(function(x) {
                        return x.name.toLowerCase() === name || x.nameEn && x.nameEn.toLowerCase() ===
                            name || x.name.toLowerCase().includes(name);
                    });
                    if (k) {
                        md[k.id] = val;
                        matched++;
                    }
                });
            } else {
                var kpis = [].concat(state.kpis).sort(function(a, b) {
                    return a.team.localeCompare(b.team) || a.order - b.order;
                });
                rows.slice(1).forEach(function(row, i) {
                    if (i >= kpis.length) return;
                    var val = row[1] !== undefined ? row[1] : row[0];
                    if (val !== '' && val !== null) {
                        md[kpis[i].id] = val;
                        matched++;
                    }
                });
            }
            state.data[mk] = md;
            var existing = state.uploadLog.findIndex(function(u) {
                return u.monthKey === mk;
            });
            var logEntry = {
                monthKey: mk,
                date: new Date().toLocaleDateString(lang === 'ar' ? 'ar-SA' : 'en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }),
                count: matched
            };
            if (existing >= 0) state.uploadLog[existing] = logEntry;
            else state.uploadLog.unshift(logEntry);
            saveState();
            closeImportModal();
            renderSidebar();
            renderMain();
            var m = ML.find(function(x) {
                return x.key === mk;
            });
            toast(T('importedMsg') + ' ' + matched + ' ' + T('kpisWord') + ' — ' + (m ? ml(m) : ''));
        }

        // ═══════════════════ EXPORT / TEMPLATE ═══════════════════
        function downloadTemplate() {
            var wb = XLSX.utils.book_new();
            var inst = [
                ['Miran OPS Performance — ' + T('importTitle')],
                [''],
                ['1. ' + T('lSelMonth')],
                ['2. ' + (lang === 'ar' ? 'أدخل القيم في العمود B' : 'Fill Column B with actual values')],
                ['3. ' + (lang === 'ar' ? 'لا تغير العمود A' : 'Do not edit Column A (KPI names)')],
                ['4. ' + (lang === 'ar' ? 'ارفع الملف عبر زر الاستيراد' : 'Upload via Import button')]
            ];
            XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(inst), lang === 'ar' ? 'التعليمات' : 'Instructions');
            var teams = ['sales', 'cs', 'coord', 'training'];
            ML.forEach(function(m) {
                var allK = teams.reduce(function(acc, t) {
                    return acc.concat(state.kpis.filter(function(k) {
                        return k.team === t;
                    }).sort(function(a, b) {
                        return a.order - b.order;
                    }));
                });
                var ws = XLSX.utils.aoa_to_sheet([
                    [T('colKpi'), 'Actual Value / القيمة الفعلية', T('colTarget'), (lang === 'ar' ?
                        'القسم' : 'Team'), T('colUnit')]
                ].concat(allK.map(function(k) {
                    return [k.name, (state.data[m.key] || {})[k.id] || '', k.target, tl(k.team), k
                        .unit
                    ];
                })));
                ws['!cols'] = [{
                    wch: 30
                }, {
                    wch: 18
                }, {
                    wch: 14
                }, {
                    wch: 22
                }, {
                    wch: 12
                }];
                var sn = ml(m).slice(0, 28);
                try {
                    XLSX.utils.book_append_sheet(wb, ws, sn);
                } catch (e) {
                    XLSX.utils.book_append_sheet(wb, ws, m.key);
                }
            });
            XLSX.writeFile(wb, 'Miran_OPS_Template_2025_2030.xlsx');
            toast(T('tplDl'));
        }

        function exportCurrentMonth() {
            var meta = getMeta();
            var md = state.data[meta.key] || {};
            var wb = XLSX.utils.book_new();
            var rows = [];
            ['sales', 'cs', 'coord', 'training'].forEach(function(t) {
                state.kpis.filter(function(k) {
                    return k.team === t;
                }).sort(function(a, b) {
                    return a.order - b.order;
                }).forEach(function(k) {
                    var val = md[k.id] || '';
                    var pct = val !== '' && k.target ? ((parseFloat(val) / k.target) * 100).toFixed(1) +
                        '%' : '';
                    rows.push([tl(t), k.name, val, k.target, k.unit, pct]);
                });
            });
            var ws = XLSX.utils.aoa_to_sheet([
                [T('oSales').includes('ال') ? 'القسم' : 'Team', T('colKpi'), T('colActual'), T('colTarget'), T(
                    'colUnit'), T('colPct')]
            ].concat(rows));
            ws['!cols'] = [{
                wch: 20
            }, {
                wch: 30
            }, {
                wch: 16
            }, {
                wch: 14
            }, {
                wch: 12
            }, {
                wch: 14
            }];
            XLSX.utils.book_append_sheet(wb, ws, ml(meta).slice(0, 28));
            XLSX.writeFile(wb, 'Miran_OPS_' + meta.key + '.xlsx');
            toast(T('monthExp'));
        }

        // ═══════════════════ INIT ═══════════════════
        function init() {
            var l = lang;
            document.body.classList.toggle('ltr', l === 'en');
            document.documentElement.lang = l;
            document.documentElement.dir = l === 'ar' ? 'rtl' : 'ltr';
            el('btnEn').classList.toggle('active', l === 'en');
            el('btnAr').classList.toggle('active', l === 'ar');
            applyStaticTrans();
            renderSidebar();
            renderMain();
        }
        init();
    </script>
</body>

</html>
