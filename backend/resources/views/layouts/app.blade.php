<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.svg') }}">
    <title>@yield('title', 'Colombia Sin Comisión - Inmuebles sin intermediarios')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #242424;
            background: #f8f8f8;
            font-synthesis: none;
            text-rendering: optimizeLegibility;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-width: 320px;
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
            background: #f8f8f8;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        input,
        select {
            font: inherit;
        }

        .site-header {
            position: relative;
            z-index: 2;
            background: #fff;
            box-shadow: 0 2px 8px rgb(25 25 25 / 8%);
        }

        .header-shell,
        .page-shell,
        .footer-shell {
            width: min(1320px, calc(100% - 48px));
            margin: 0 auto;
        }

        .header-top {
            min-height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #202124;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -1.2px;
            white-space: nowrap;
        }

        .brand-mark {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: #ffe22d;
            color: #252525;
        }

        .brand-mark svg {
            width: 22px;
            height: 22px;
        }

        .brand-accent {
            color: #159a8c;
        }

        .header-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 24px;
            color: #525252;
            font-size: 14px;
        }

        .header-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: color 160ms ease;
        }

        .header-link:hover,
        .category-link:hover {
            color: #12877b;
        }

        .header-link svg {
            width: 18px;
            height: 18px;
            flex: none;
        }

        .publish-link {
            padding: 11px 18px;
            border-radius: 999px;
            background: #ffe22d;
            color: #242424;
            font-weight: 750;
            transition: background 160ms ease, transform 160ms ease;
        }

        .publish-link:hover {
            background: #f5d417;
            transform: translateY(-1px);
        }

        .category-nav {
            min-height: 58px;
            display: flex;
            align-items: stretch;
            gap: clamp(20px, 4vw, 62px);
            overflow-x: auto;
            border-top: 1px solid #f0f0f0;
            scrollbar-width: none;
        }

        .category-nav::-webkit-scrollbar {
            display: none;
        }

        .category-link {
            position: relative;
            flex: none;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: #3b3b3b;
            font-size: 15px;
            font-weight: 650;
            transition: color 160ms ease;
        }

        .category-link svg {
            width: 19px;
            height: 19px;
            color: #666;
        }

        .category-link.is-active::after {
            position: absolute;
            right: 0;
            bottom: 0;
            left: 0;
            height: 3px;
            border-radius: 3px 3px 0 0;
            background: #159a8c;
            content: "";
        }

        .category-logout-form {
            display: flex;
            flex: none;
            align-items: stretch;
        }

        .category-logout-button {
            padding: 0;
            border: 0;
            background: transparent;
            cursor: pointer;
        }

        .category-logout-button:hover {
            color: #b42318;
        }

        main {
            width: 100%;
            max-width: 1320px;
            margin: 0 auto;
            padding: 0 24px;
            flex: 1;
        }

        .page-shell {
            width: auto;
            margin: 0;
        }

        .page-shell {
            padding-top: 26px;
            padding-bottom: 56px;
        }

        .campaign {
            min-height: 340px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            overflow: hidden;
            border-radius: 0 0 18px 18px;
            background: #f4b8ce;
        }

        .campaign-copy {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            padding: 38px clamp(28px, 6vw, 86px);
        }

        .campaign-kicker {
            margin: 0 0 12px;
            color: #744958;
            font-size: 13px;
            font-weight: 750;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .campaign h1 {
            max-width: 540px;
            margin: 0;
            color: #252123;
            font-size: clamp(36px, 4vw, 58px);
            font-weight: 800;
            letter-spacing: -0.055em;
            line-height: 1.04;
        }

        .campaign-description {
            max-width: 430px;
            margin: 17px 0 0;
            color: #513d44;
            font-size: 18px;
            line-height: 1.55;
        }

        .campaign-note {
            margin: 22px 0 0;
            color: #513d44;
            font-size: 14px;
            font-weight: 650;
        }

        .campaign-photo {
            position: relative;
            min-height: 340px;
            overflow: hidden;
            background: linear-gradient(145deg, #9cdbd2 0%, #59a99d 52%, #286f68 100%);
        }

        .campaign-photo img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .campaign-photo::after {
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgb(30 43 41 / 3%), rgb(18 34 32 / 20%));
            content: "";
        }

        .campaign-stamp {
            position: absolute;
            z-index: 1;
            right: 30px;
            bottom: 24px;
            max-width: calc(100% - 60px);
            padding: 12px 18px;
            border-radius: 12px;
            background: rgb(255 255 255 / 93%);
            color: #242424;
            font-size: 14px;
            font-weight: 700;
            box-shadow: 0 8px 24px rgb(0 0 0 / 14%);
        }

        .campaign-stamp span {
            display: block;
            margin-top: 3px;
            color: #737373;
            font-size: 12px;
            font-weight: 500;
        }

        .search-panel {
            position: relative;
            z-index: 1;
            margin: 22px 5% 0;
            padding: 25px 30px 30px;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 5px 18px rgb(31 41 55 / 10%);
        }

        .search-panel h2 {
            margin: 0 0 22px;
            color: #2d2d2d;
            font-size: 23px;
            font-weight: 750;
            letter-spacing: -0.03em;
        }

        .search-fields {
            display: grid;
            grid-template-columns: minmax(180px, 1.4fr) minmax(150px, 1fr) minmax(160px, 1fr) auto;
            align-items: end;
            gap: 16px;
        }

        .search-field {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
            color: #646464;
            font-size: 12px;
            font-weight: 700;
        }

        .search-input-wrap {
            min-height: 43px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1.5px solid #8c8c8c;
            transition: border-color 160ms ease;
        }

        .search-input-wrap:focus-within {
            border-color: #159a8c;
        }

        .search-input-wrap svg {
            width: 20px;
            height: 20px;
            flex: none;
            color: #424242;
        }

        .search-input-wrap input,
        .search-input-wrap select {
            width: 100%;
            min-width: 0;
            border: 0;
            outline: 0;
            background: transparent;
            color: #282828;
            font-size: 14px;
        }

        .search-input-wrap input::placeholder {
            color: #999;
        }

        .search-button {
            min-height: 52px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 0 26px;
            border: 0;
            border-radius: 999px;
            background: #ffdf24;
            color: #252525;
            cursor: pointer;
            font-size: 15px;
            font-weight: 750;
            transition: background 160ms ease, transform 160ms ease;
        }

        .search-button:hover {
            background: #f4d116;
            transform: translateY(-1px);
        }

        .search-button svg {
            width: 19px;
            height: 19px;
        }

        .listings-section {
            padding: 38px 16px 0;
        }

        .property-type-section {
            margin-top: 30px;
            padding: 25px;
            border-radius: 20px;
            background: var(--section-background, #f0fbfa);
        }

        .property-type-section--aqua {
            --section-accent: #89d9e8;
            --section-background: #edfafd;
        }

        .property-type-section--rose {
            --section-accent: #f2b7ce;
            --section-background: #fff2f7;
        }

        .property-type-section--yellow {
            --section-accent: #f7d85c;
            --section-background: #fffbea;
        }

        .property-type-section--lavender {
            --section-accent: #c7b1e6;
            --section-background: #f7f3fc;
        }

        .property-type-section--orange {
            --section-accent: #ff9a5f;
            --section-background: #fff4ed;
        }

        .property-type-section--teal {
            --section-accent: #8dd8cc;
            --section-background: #effaf8;
        }

        .section-heading {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
        }

        .section-heading h2 {
            margin: 0;
            color: #282828;
            font-size: 25px;
            font-weight: 780;
            letter-spacing: -0.04em;
        }

        .section-heading p {
            margin: 6px 0 0;
            color: #717171;
            font-size: 14px;
        }

        .all-listings-link {
            flex: none;
            color: #12877b;
            font-size: 14px;
            font-weight: 700;
        }

        .property-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
        }

        .property-card {
            min-width: 0;
            overflow: hidden;
            border: 1px solid rgb(36 36 36 / 8%);
            border-top: 6px solid var(--section-accent, #a6ddd5);
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 3px 12px rgb(31 41 55 / 5%);
            transition: transform 180ms ease, box-shadow 180ms ease;
        }

        .property-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 24px rgb(31 41 55 / 10%);
        }

        .property-image {
            height: 220px;
            display: block;
            overflow: hidden;
            background: linear-gradient(135deg, #d7efeb, #a7d5ce);
        }

        .property-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 220ms ease;
        }

        .property-card:hover .property-image img {
            transform: scale(1.035);
        }

        .property-image-placeholder {
            width: 100%;
            height: 100%;
            display: grid;
            place-items: center;
            color: #328b80;
        }

        .property-image-placeholder svg {
            width: 58px;
            height: 58px;
        }

        .property-details {
            padding: 17px 18px 19px;
        }

        .property-type {
            margin: 0 0 7px;
            color: #12877b;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .property-operation {
            display: inline-flex;
            align-items: center;
            min-height: 24px;
            margin: 0 0 8px;
            padding: 3px 9px;
            border: 1px solid #d5ebe6;
            border-radius: 999px;
            background: #eef8f6;
            color: #08776c;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .property-operation--sale,
        .property-operation-badge--sale {
            border-color: #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .property-operation--rental,
        .property-operation-badge--rental {
            border-color: #a7f3d0;
            background: #ecfdf5;
            color: #047857;
        }

        .dashboard-operation-badge {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            margin-top: 5px;
            padding: 3px 8px;
            border: 1px solid #d5ebe6;
            border-radius: 999px;
            background: #eef8f6;
            color: #08776c;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .dashboard-operation-badge--sale {
            border-color: #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .dashboard-operation-badge--rental {
            border-color: #a7f3d0;
            background: #ecfdf5;
            color: #047857;
        }

        .property-title {
            margin: 0;
            overflow: hidden;
            color: #292929;
            font-size: 17px;
            font-weight: 730;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .property-location {
            margin: 8px 0 16px;
            color: #777;
            font-size: 13px;
        }

        .property-card-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .property-card-facts {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 12px;
            margin-top: 12px;
            color: #66716e;
            font-size: 11px;
            font-weight: 650;
        }

        .property-price {
            color: #292929;
            font-size: 18px;
            font-weight: 800;
        }

        .property-details-link {
            color: #12877b;
            font-size: 13px;
            font-weight: 750;
        }

        .empty-results {
            padding: 36px 20px;
            border: 1px dashed #d4d4d4;
            border-radius: 14px;
            background: #fff;
            color: #666;
            text-align: center;
        }

        .empty-results h2 {
            margin: 0;
            color: #333;
            font-size: 20px;
        }

        .empty-results p {
            margin: 8px 0 14px;
        }

        .catalog-intro {
            padding: 30px 8px 22px;
        }

        .catalog-eyebrow {
            margin: 0 0 8px;
            color: #53677e;
            font-size: 12px;
            font-weight: 750;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .catalog-intro h1 {
            margin: 0;
            color: #252a31;
            font-size: clamp(30px, 4vw, 44px);
            font-weight: 800;
            letter-spacing: -0.045em;
            line-height: 1.1;
        }

        .catalog-intro > p:last-child {
            margin: 10px 0 0;
            color: #69717d;
            font-size: 16px;
        }

        .catalog-search {
            padding: 17px;
            border: 1px solid #e6e9ee;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 3px 12px rgb(31 41 55 / 5%);
        }

        .catalog-search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            align-items: end;
            gap: 12px;
        }

        .catalog-search-field {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 7px;
            color: #485262;
            font-size: 12px;
            font-weight: 700;
        }

        .catalog-search-field input,
        .catalog-search-field select {
            width: 100%;
            min-height: 46px;
            padding: 0 13px;
            border: 1px solid #d9dee6;
            border-radius: 9px;
            outline: none;
            color: #2d3440;
            font-size: 14px;
        }

        .catalog-search-field input:focus,
        .catalog-search-field select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgb(37 99 235 / 12%);
        }

        .catalog-search-button {
            min-height: 46px;
            justify-self: start;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 0 22px;
            border: 0;
            border-radius: 9px;
            background: #ffdf24;
            color: #242424;
            cursor: pointer;
            font-size: 14px;
            font-weight: 750;
            white-space: nowrap;
        }

        .catalog-search-button:hover {
            background: #f4d116;
        }

        .catalog-search-button svg {
            width: 18px;
            height: 18px;
        }

        .catalog-type-nav {
            display: flex;
            gap: 8px;
            margin-top: 20px;
            padding-bottom: 3px;
            overflow-x: auto;
            scrollbar-width: thin;
        }

        .catalog-type-link {
            flex: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 17px;
            border: 1px solid #dce2eb;
            border-radius: 999px;
            background: #fff;
            color: #394252;
            font-size: 13px;
            font-weight: 700;
            transition: color 150ms ease, background 150ms ease, border-color 150ms ease;
        }

        .catalog-type-link:hover {
            border-color: #2563eb;
            color: #1d4ed8;
        }

        .catalog-type-link.is-active {
            border-color: #2563eb;
            background: #2563eb;
            color: #fff;
            box-shadow: 0 3px 8px rgb(37 99 235 / 20%);
        }

        .catalog-results {
            padding: 30px 8px 0;
        }

        .catalog-results-heading {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
        }

        .catalog-results-heading h2 {
            margin: 0;
            color: #2b3038;
            font-size: 23px;
            font-weight: 780;
            letter-spacing: -0.03em;
        }

        .catalog-results-heading p {
            margin: 6px 0 0;
            color: #777f8a;
            font-size: 13px;
        }

        .catalog-clear-link {
            flex: none;
            color: #2563eb;
            font-size: 13px;
            font-weight: 700;
        }

        .catalog-property-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .catalog-property-card {
            border-top: 1px solid #ededed;
        }

        .catalog-pagination {
            display: flex;
            justify-content: center;
            margin-top: 28px;
        }

        .catalog-empty-state {
            padding: 50px 20px;
            border: 1px dashed #ccd3dd;
            border-radius: 14px;
            background: #fff;
            text-align: center;
        }

        .catalog-empty-state h3 {
            margin: 0;
            color: #303744;
            font-size: 20px;
        }

        .catalog-empty-state p {
            margin: 9px 0 20px;
            color: #6e7784;
        }

        .advisory-section {
            padding: 42px 16px 10px;
        }

        .advisory-heading {
            margin-bottom: 20px;
        }

        .advisory-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .advisory-card {
            min-width: 0;
            min-height: 282px;
            display: grid;
            grid-template-columns: 1fr 0.9fr;
            overflow: hidden;
            border-radius: 20px;
        }

        .advisory-card--finance {
            background: #ffe77d;
        }

        .advisory-card--publish {
            background: #cfb8e9;
        }

        .advisory-copy {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            padding: 27px clamp(20px, 2.3vw, 34px);
        }

        .advisory-eyebrow {
            margin: 0 0 8px;
            color: rgb(36 36 36 / 68%);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .advisory-copy h3 {
            margin: 0;
            color: #292929;
            font-size: clamp(19px, 1.8vw, 26px);
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1.12;
        }

        .advisory-copy > p:not(.advisory-eyebrow) {
            margin: 13px 0 18px;
            color: #514c46;
            font-size: 14px;
            line-height: 1.48;
        }

        .advisory-button {
            min-height: 43px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 0 17px;
            border-radius: 999px;
            background: #292929;
            color: #fff;
            font-size: 13px;
            font-weight: 750;
            transition: background 160ms ease, transform 160ms ease;
        }

        .advisory-button:hover {
            transform: translateY(-1px);
            background: #444;
        }

        .advisory-button span {
            font-size: 19px;
            line-height: 1;
        }

        .advisory-photo {
            min-height: 100%;
            background-position: center;
            background-size: cover;
        }

        .advisory-photo--finance {
            background-image: url("https://images.unsplash.com/photo-1554224155-6726b3ff858f?auto=format&fit=crop&w=900&q=85");
        }

        .advisory-photo--publish {
            background-image: url("https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=900&q=85");
        }

        .dashboard-shell {
            padding: 34px 0 56px;
        }

        .dashboard-heading,
        .dashboard-table-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .dashboard-heading {
            margin-bottom: 25px;
        }

        .dashboard-eyebrow {
            margin: 0 0 7px;
            color: #16877c;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .dashboard-heading h1 {
            margin: 0;
            color: #252a31;
            font-size: clamp(28px, 4vw, 38px);
            font-weight: 800;
            letter-spacing: -0.045em;
        }

        .dashboard-heading p:not(.dashboard-eyebrow) {
            margin: 8px 0 0;
            color: #69717d;
            font-size: 14px;
        }

        .dashboard-add-button {
            min-height: 43px;
            display: inline-flex;
            flex: none;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 17px;
            border: 0;
            border-radius: 999px;
            background: #ffe22d;
            color: #242424;
            cursor: pointer;
            font-size: 13px;
            font-weight: 750;
            transition: background 160ms ease, transform 160ms ease;
        }

        .dashboard-add-button:hover {
            transform: translateY(-1px);
            background: #f5d417;
        }

        .dashboard-add-button:disabled {
            cursor: wait;
            opacity: 0.65;
        }

        .dashboard-add-button span {
            font-size: 20px;
            line-height: 1;
        }

        .dashboard-notice {
            margin-bottom: 20px;
            padding: 13px 16px;
            border: 1px solid #b7e4ce;
            border-radius: 10px;
            background: #effaf3;
            color: #17623c;
            font-size: 14px;
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .dashboard-stat {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 18px 20px;
            border: 1px solid #e8ebee;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 3px 12px rgb(31 41 55 / 4%);
        }

        .dashboard-stat span {
            color: #69717d;
            font-size: 13px;
            font-weight: 600;
        }

        .dashboard-stat strong {
            color: #252a31;
            font-size: 26px;
            font-weight: 800;
        }

        .dashboard-table-card {
            overflow: hidden;
            border: 1px solid #e7eaee;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 4px 16px rgb(31 41 55 / 5%);
        }

        .dashboard-table-heading {
            padding: 21px 23px;
            border-bottom: 1px solid #edf0f2;
        }

        .dashboard-table-heading h2 {
            margin: 0;
            color: #2b3038;
            font-size: 18px;
            font-weight: 780;
        }

        .dashboard-table-heading p {
            margin: 5px 0 0;
            color: #777f8a;
            font-size: 13px;
        }

        .dashboard-table-scroll {
            overflow-x: auto;
        }

        .dashboard-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .dashboard-table th {
            padding: 12px 15px;
            background: #f8fafb;
            color: #6d7580;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .dashboard-table td {
            padding: 15px;
            border-top: 1px solid #eff1f3;
            color: #424852;
            font-size: 13px;
            vertical-align: middle;
        }

        .dashboard-property {
            min-width: 245px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .dashboard-property-image {
            width: 76px;
            height: 60px;
            flex: none;
            display: grid;
            overflow: hidden;
            place-items: center;
            border-radius: 9px;
            background: #eef8f6;
            color: #159a8c;
        }

        .dashboard-property-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .dashboard-property-image svg {
            width: 26px;
            height: 26px;
        }

        .dashboard-property-copy {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .dashboard-property-copy a {
            max-width: 230px;
            overflow: hidden;
            color: #2b3038;
            font-weight: 750;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dashboard-property-copy a:hover {
            color: #12877b;
        }

        .dashboard-property-copy span {
            color: #828994;
            font-size: 11px;
        }

        .dashboard-price {
            min-width: 112px;
            color: #303640 !important;
            font-weight: 750;
            white-space: nowrap;
        }

        .dashboard-status {
            display: inline-flex;
            padding: 5px 10px;
            border-radius: 999px;
            background: #e8f6ee;
            color: #267346;
            font-size: 11px;
            font-weight: 750;
        }

        .dashboard-media-actions,
        .dashboard-property-actions,
        .dashboard-video-actions {
            min-width: 145px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 7px;
        }

        .dashboard-media-actions form,
        .dashboard-property-actions form {
            margin: 0;
        }

        .dashboard-action-button {
            min-height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 0 9px;
            border: 1px solid transparent;
            border-radius: 8px;
            background: #fff;
            cursor: pointer;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
            transition: background 140ms ease, border-color 140ms ease;
        }

        .dashboard-action-button svg {
            width: 14px;
            height: 14px;
            flex: none;
        }

        .dashboard-action-button--view {
            border-color: #d9e8ff;
            background: #f2f7ff;
            color: #2360ad;
        }

        .dashboard-action-button--edit {
            border-color: #f6e6b7;
            background: #fffaf0;
            color: #8b6813;
        }

        .dashboard-action-button--delete {
            border-color: #f4d6d4;
            background: #fff6f5;
            color: #ac4038;
        }

        .dashboard-action-button--create,
        .dashboard-action-button--video {
            border-color: #bde7df;
            background: #f0faf8;
            color: #167e72;
        }

        .dashboard-action-button:hover {
            filter: brightness(0.97);
        }

        .dashboard-video-actions {
            min-width: 125px;
            flex-direction: column;
            align-items: flex-start;
        }

        .dashboard-video-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #176f83;
            font-size: 11px;
            font-weight: 700;
        }

        .dashboard-video-link svg {
            width: 15px;
            height: 15px;
        }

        .dashboard-muted {
            color: #949aa3;
            font-size: 11px;
        }

        .dashboard-pagination {
            padding: 17px 20px;
            border-top: 1px solid #edf0f2;
        }

        .dashboard-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 45px 20px;
            text-align: center;
        }

        .dashboard-empty-icon {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            border-radius: 15px;
            background: #fff5c2;
            color: #292929;
            font-size: 29px;
        }

        .dashboard-empty h3 {
            margin: 15px 0 0;
            color: #2b3038;
            font-size: 18px;
        }

        .dashboard-empty p {
            margin: 7px 0 18px;
            color: #777f8a;
            font-size: 13px;
        }

        .video-upload-dialog {
            width: min(500px, calc(100% - 32px));
            padding: 0;
            border: 0;
            border-radius: 18px;
            background: #fff;
            color: #292929;
            box-shadow: 0 24px 80px rgb(0 0 0 / 24%);
        }

        .video-upload-dialog::backdrop {
            background: rgb(24 30 39 / 58%);
            backdrop-filter: blur(3px);
        }

        .video-upload-dialog form {
            padding: 25px;
        }

        .video-dialog-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 23px;
        }

        .video-dialog-heading h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .video-dialog-heading p:last-child {
            margin: 6px 0 0;
            color: #747b86;
            font-size: 13px;
        }

        .video-dialog-close {
            width: 34px;
            height: 34px;
            flex: none;
            border: 0;
            border-radius: 50%;
            background: #f1f3f5;
            color: #4b5159;
            cursor: pointer;
            font-size: 23px;
            line-height: 1;
        }

        .video-file-label {
            display: block;
            margin-bottom: 8px;
            color: #3d4652;
            font-size: 13px;
            font-weight: 750;
        }

        .video-file-input {
            width: 100%;
            padding: 12px;
            border: 1px dashed #b8c1cc;
            border-radius: 10px;
            background: #f9fafb;
            color: #5d6570;
            font-size: 12px;
        }

        .video-file-help {
            margin: 9px 0 0;
            color: #747b86;
            font-size: 12px;
            line-height: 1.5;
        }

        .video-file-error {
            min-height: 18px;
            margin: 7px 0 0;
            color: #bd332a;
            font-size: 12px;
        }

        .video-dialog-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 18px;
        }

        .video-cancel-button {
            min-height: 43px;
            padding: 0 16px;
            border: 1px solid #dfe3e8;
            border-radius: 999px;
            background: #fff;
            color: #515862;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
        }

        .site-footer {
            background: #222a2a;
            color: #d6d9d8;
        }

        .footer-top {
            padding: 16px;
            background: #303a39;
            color: #fff;
            text-align: center;
        }

        .footer-top a:hover,
        .footer-column a:hover {
            text-decoration: underline;
        }

        .footer-columns {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 34px;
            padding: 38px 0;
        }

        .footer-column h2 {
            margin: 0 0 14px;
            color: #fff;
            font-size: 15px;
            font-weight: 750;
        }

        .footer-column a {
            display: block;
            margin-top: 10px;
            color: #c1c7c5;
            font-size: 13px;
        }

        .footer-bottom {
            padding: 19px 24px;
            border-top: 1px solid rgb(255 255 255 / 12%);
            color: #aeb5b3;
            font-size: 12px;
            text-align: center;
        }

        @media (max-width: 850px) {
            .header-shell,
            .footer-shell {
                width: min(100% - 32px, 700px);
            }

            main {
                padding-right: 16px;
                padding-left: 16px;
            }

            .header-top {
                min-height: 68px;
            }

            .header-actions {
                gap: 14px;
            }

            .header-help {
                display: none;
            }

            .category-nav {
                gap: 28px;
            }

            .campaign {
                min-height: 300px;
                grid-template-columns: 1.05fr 0.95fr;
            }

            .campaign-copy,
            .campaign-photo {
                min-height: 300px;
            }

            .campaign-copy {
                padding: 30px;
            }

            .campaign-description {
                font-size: 16px;
            }

            .search-panel {
                margin-right: 2%;
                margin-left: 2%;
            }

            .search-fields {
                grid-template-columns: minmax(0, 1fr) minmax(0, 0.9fr);
            }

            .search-button {
                grid-column: 1 / -1;
            }

            .property-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dashboard-table {
                min-width: 1050px;
            }

            .catalog-property-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .advisory-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        @media (max-width: 580px) {
            .header-shell,
            .footer-shell {
                width: min(100% - 24px, 460px);
            }

            main {
                padding-right: 12px;
                padding-left: 12px;
            }

            .header-top {
                min-height: 64px;
                gap: 12px;
            }

            .brand {
                gap: 8px;
                font-size: 19px;
                letter-spacing: -0.8px;
            }

            .brand-mark {
                width: 33px;
                height: 33px;
                border-radius: 10px;
            }

            .header-actions {
                gap: 0;
                font-size: 12px;
            }

            .header-actions .publish-link {
                padding: 9px 12px;
                font-size: 12px;
            }

            .category-logout-button {
                font-size: 12px;
            }

            .category-nav {
                min-height: 52px;
                gap: 24px;
            }

            .category-link {
                font-size: 13px;
            }

            .category-link svg {
                width: 17px;
                height: 17px;
            }

            .page-shell {
                padding-top: 16px;
                padding-bottom: 38px;
            }

            .campaign {
                display: flex;
                flex-direction: column;
                border-radius: 14px;
            }

            .campaign-copy {
                min-height: 0;
                padding: 28px 24px 23px;
            }

            .campaign-kicker {
                margin-bottom: 9px;
                font-size: 11px;
            }

            .campaign h1 {
                max-width: 390px;
                font-size: clamp(34px, 10vw, 46px);
            }

            .campaign-description {
                margin-top: 12px;
                font-size: 15px;
            }

            .campaign-note {
                margin-top: 15px;
                font-size: 12px;
            }

            .campaign-photo {
                min-height: 180px;
            }

            .campaign-stamp {
                right: 16px;
                bottom: 14px;
                max-width: calc(100% - 32px);
                padding: 9px 12px;
                font-size: 12px;
            }

            .search-panel {
                margin: 14px 0 0;
                padding: 20px 18px;
                border-radius: 14px;
            }

            .search-panel h2 {
                margin-bottom: 16px;
                font-size: 20px;
            }

            .search-fields {
                grid-template-columns: minmax(0, 1fr);
                gap: 16px;
            }

            .search-button {
                grid-column: auto;
                width: 100%;
            }

            .listings-section {
                padding: 31px 0 0;
            }

            .section-heading {
                align-items: flex-start;
            }

            .section-heading h2 {
                font-size: 21px;
            }

            .section-heading p {
                font-size: 13px;
            }

            .all-listings-link {
                padding-top: 5px;
                font-size: 12px;
            }

            .property-grid {
                grid-template-columns: minmax(0, 1fr);
                gap: 15px;
            }

            .catalog-intro {
                padding: 23px 2px 18px;
            }

            .catalog-intro > p:last-child {
                font-size: 14px;
            }

            .catalog-search {
                padding: 13px;
            }

            .catalog-search-form {
                grid-template-columns: minmax(0, 1fr);
            }

            .catalog-search-button {
                width: 100%;
            }

            .catalog-type-nav {
                margin-top: 15px;
            }

            .catalog-type-link {
                min-height: 39px;
                padding: 0 14px;
                font-size: 12px;
            }

            .catalog-results {
                padding: 25px 0 0;
            }

            .catalog-results-heading h2 {
                font-size: 20px;
            }

            .catalog-property-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .dashboard-shell {
                padding: 24px 0 38px;
            }

            .dashboard-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .dashboard-stats {
                gap: 9px;
            }

            .dashboard-stat {
                padding: 14px 12px;
            }

            .dashboard-stat span {
                font-size: 11px;
            }

            .dashboard-stat strong {
                font-size: 22px;
            }

            .dashboard-table-heading {
                align-items: flex-start;
                padding: 17px;
            }

            .video-upload-dialog form {
                padding: 21px;
            }

            .catalog-clear-link {
                font-size: 12px;
            }

            .property-type-section {
                margin-top: 20px;
                padding: 17px;
                border-radius: 16px;
            }

            .advisory-section {
                padding: 32px 0 4px;
            }

            .advisory-grid {
                gap: 14px;
            }

            .advisory-card {
                grid-template-columns: minmax(0, 1fr);
            }

            .advisory-copy {
                padding: 23px;
            }

            .advisory-copy h3 {
                font-size: 23px;
            }

            .advisory-copy > p:not(.advisory-eyebrow) {
                font-size: 13px;
            }

            .advisory-photo {
                min-height: 180px;
            }

            .property-image {
                height: 210px;
            }

            .footer-columns {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 25px 18px;
                padding: 30px 0;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="header-shell">
            <div class="header-top">
                <a href="{{ route('home') }}" class="brand" aria-label="Colombia Sin Comisión, inicio">
                    <span class="brand-mark" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m3 10 9-7 9 7"></path>
                            <path d="M5 9v11h14V9"></path>
                            <path d="M9 20v-6h6v6"></path>
                        </svg>
                    </span>
                    <span>Colombia <span class="brand-accent">Sin Comisión</span></span>
                </a>

                <div class="header-actions">
                    @auth
                        <a href="{{ route('dashboard.index') }}" class="header-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="8" r="4"></circle>
                                <path d="M4 21a8 8 0 0 1 16 0"></path>
                            </svg>
                            Mi cuenta
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="header-link header-help">Iniciar sesión</a>
                    @endauth
                    <a href="{{ route('properties.create') }}" class="publish-link">Publica tu inmueble</a>
                </div>
            </div>

            <nav class="category-nav" aria-label="Categorías de inmuebles">
                <a href="{{ route('properties.index', array_filter(['listing_type' => request()->query('listing_type')])) }}" class="category-link {{ request()->routeIs('properties.index') && !request()->query('property_type') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m3 10 9-7 9 7"></path><path d="M5 9v11h14V9"></path><path d="M9 20v-6h6v6"></path>
                    </svg>
                    Todos
                </a>
                <a href="{{ route('properties.index', array_filter(['property_type' => 'apartamento', 'listing_type' => request()->query('listing_type')])) }}" class="category-link {{ request()->query('property_type') === 'apartamento' ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 21V5a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v16"></path><path d="M17 9h3v12"></path><path d="M2 21h20"></path><path d="M8 7h1m4 0h1M8 11h1m4 0h1M8 15h1m4 0h1"></path>
                    </svg>
                    Apartamentos
                </a>
                <a href="{{ route('properties.index', array_filter(['property_type' => 'casa', 'listing_type' => request()->query('listing_type')])) }}" class="category-link {{ request()->query('property_type') === 'casa' ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m3 10 9-7 9 7"></path><path d="M5 9v11h14V9"></path><path d="M9 20v-6h6v6"></path>
                    </svg>
                    Casas
                </a>
                <a href="{{ route('properties.index', array_filter(['property_type' => 'finca', 'listing_type' => request()->query('listing_type')])) }}" class="category-link {{ request()->query('property_type') === 'finca' ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 20h18"></path><path d="M5 20V9l7-5 7 5v11"></path><path d="M9 20v-5h6v5"></path><path d="M3 11h2m14 0h2"></path>
                    </svg>
                    Fincas
                </a>
                <a href="{{ route('properties.index', array_filter(['property_type' => 'lote', 'listing_type' => request()->query('listing_type')])) }}" class="category-link {{ request()->query('property_type') === 'lote' ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 19 7 6l10-2 3 14-9 3-7-2Z"></path><path d="m7 6 4 15m6-17-1 16"></path>
                    </svg>
                    Lotes
                </a>
                <a href="{{ route('properties.index', array_filter(['property_type' => 'oficina', 'listing_type' => request()->query('listing_type')])) }}" class="category-link {{ request()->query('property_type') === 'oficina' ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 21V5a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v16"></path><path d="M17 9h3v12"></path><path d="M2 21h20"></path><path d="M8 7h1m4 0h1M8 11h1m4 0h1M8 15h1m4 0h1"></path>
                    </svg>
                    Oficinas
                </a>
                <a href="{{ route('properties.index', array_filter(['property_type' => 'comercial', 'listing_type' => request()->query('listing_type')])) }}" class="category-link {{ in_array(request()->query('property_type'), ['comercial', 'local'], true) ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 10h18l-1.5-6h-15L3 10Z"></path><path d="M5 10v11h14V10"></path><path d="M9 21v-7h6v7"></path><path d="M3 10a2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0"></path>
                    </svg>
                    Comerciales
                </a>
                <a href="{{ route('properties.index', array_filter(['property_type' => 'bodega', 'listing_type' => request()->query('listing_type')])) }}" class="category-link {{ request()->query('property_type') === 'bodega' ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 21V9l9-6 9 6v12"></path><path d="M3 10h18"></path><path d="M7 21v-7h10v7"></path><path d="M10 14v7m4-7v7"></path>
                    </svg>
                    Bodegas
                </a>
                @if(request()->routeIs('dashboard.index'))
                    <form action="{{ route('logout') }}" method="POST" class="category-logout-form">
                        @csrf
                        <button type="submit" class="category-link category-logout-button">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M10 17l5-5-5-5"></path><path d="M15 12H3"></path><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"></path>
                            </svg>
                            Cerrar sesión
                        </button>
                    </form>
                @endif
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="footer-top">
            <a href="{{ route('home') }}">Volver arriba ↑</a>
        </div>
        <div class="footer-shell footer-columns">
            <div class="footer-column">
                <h2>Conócenos</h2>
                <a href="{{ route('home') }}">Acerca de Colombia Sin Comisión</a>
                <a href="{{ route('properties.create') }}">Publica tu inmueble</a>
            </div>
            <div class="footer-column">
                <h2>Explora</h2>
                <a href="{{ route('properties.index', array_filter(['property_type' => 'apartamento', 'listing_type' => request()->query('listing_type')])) }}">Apartamentos</a>
                <a href="{{ route('properties.index', array_filter(['property_type' => 'casa', 'listing_type' => request()->query('listing_type')])) }}">Casas</a>
                <a href="{{ route('properties.index', array_filter(['property_type' => 'oficina', 'listing_type' => request()->query('listing_type')])) }}">Oficinas</a>
                <a href="{{ route('properties.index', array_filter(['property_type' => 'comercial', 'listing_type' => request()->query('listing_type')])) }}">Propiedades comerciales</a>
                <a href="{{ route('properties.index', array_filter(['property_type' => 'bodega', 'listing_type' => request()->query('listing_type')])) }}">Bodegas</a>
                <a href="{{ route('properties.index', ['listing_type' => 'venta']) }}">Inmuebles en venta</a>
                <a href="{{ route('properties.index', ['listing_type' => 'alquiler']) }}">Inmuebles en alquiler</a>
            </div>
            <div class="footer-column">
                <h2>Podemos ayudarte</h2>
                <a href="{{ route('login') }}">Centro de ayuda</a>
                <a href="{{ route('login') }}">Inicia sesión</a>
            </div>
            <div class="footer-column">
                <h2>Encuéntranos</h2>
                <a href="{{ route('home') }}">Colombia · Hogares sin intermediarios</a>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; {{ date('Y') }} Colombia Sin Comisión S.A.S. Todos los derechos reservados.
        </div>
    </footer>
</body>
</html>
