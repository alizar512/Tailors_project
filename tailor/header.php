<?php
require_once 'auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tailor Portal | Silah</title>
    
    <!-- Fonts & CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Inter:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Tailwind CSS (Local Build) -->
    <link rel="stylesheet" href="../css/tailwind.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        :root {
            --tailor-bg: #ffffff;
            --tailor-surface: #ffffff;
            --tailor-surface-2: #ffffff;
            --tailor-border: rgba(134, 82, 148, 0.12);
            --tailor-border-2: rgba(134, 82, 148, 0.22);
            --tailor-text: #2D1B36;
            --tailor-muted: #6B7280;
            --tailor-primary: #865294;
            --tailor-primary-2: #6D3B7A;
            --tailor-accent-light: #F3E8FF;
            --tailor-sidebar: #2D1B36;
            --tailor-shadow: 0 16px 50px rgba(45, 27, 54, 0.12);
        }
        
        body {
            background-color: var(--tailor-bg);
            font-family: 'Poppins', sans-serif;
            color: var(--tailor-text);
            background-image: none;
        }

        aside {
            background: var(--tailor-sidebar) !important;
        }

        header.lg\\:hidden {
            background: var(--tailor-sidebar) !important;
        }

        .text-primary {
            color: var(--tailor-primary) !important;
        }

        .bg-primary {
            background-color: var(--tailor-primary) !important;
        }

        .border-primary {
            border-color: rgba(134, 82, 148, 0.40) !important;
        }
        
        .glass-card {
            background: var(--card-bg, var(--tailor-surface));
            border: 1px solid var(--card-border, var(--tailor-border));
            border-radius: 24px;
            box-shadow: var(--tailor-shadow);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .glass-card:hover {
            box-shadow: 0 22px 70px rgba(45, 27, 54, 0.16);
            border-color: var(--card-border-hover, var(--tailor-border-2));
            transform: translateY(-1px);
        }
        
        .sidebar-link {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 16px;
            margin: 4px 0;
            border-left: 4px solid transparent;
        }
        
        .sidebar-link:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateX(5px);
        }

        .sidebar-link.active {
            background: linear-gradient(135deg, rgba(134, 82, 148, 0.95), rgba(109, 59, 122, 0.92));
            color: #FFF3E1 !important;
            border-left: 4px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 22px 60px rgba(134, 82, 148, 0.24);
        }

        .stat-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
        }

        .btn-primary {
            background: linear-gradient(135deg, rgba(134, 82, 148, 0.95), rgba(109, 59, 122, 0.95));
            border: none;
            border-radius: 16px;
            transition: all 0.3s ease;
            color: #fff;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, rgba(134, 82, 148, 0.92), rgba(109, 59, 122, 0.92));
            transform: translateY(-2px);
            box-shadow: 0 18px 60px rgba(134, 82, 148, 0.25);
            color: #fff;
        }

        .form-control, .form-select {
            border-radius: 16px;
            padding: 0.75rem 1.25rem;
            border: 1px solid rgba(134, 82, 148, 0.14);
            background: #ffffff;
            color: var(--tailor-text);
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: rgba(134, 82, 148, 0.60);
            box-shadow: 0 0 0 4px rgba(134, 82, 148, 0.18);
        }

        .form-control::placeholder {
            color: rgba(107, 114, 128, 0.75);
        }

        .btn {
            border-radius: 16px;
        }

        .btn-outline {
            border-width: 2px;
            border-color: rgba(134, 82, 148, 0.35);
            color: rgba(134, 82, 148, 0.98);
            background: #ffffff;
        }

        .btn-outline:hover {
            border-color: rgba(134, 82, 148, 0.70);
            background: rgba(134, 82, 148, 0.12);
            color: rgba(109, 59, 122, 0.98);
        }

        .table {
            --bs-table-bg: transparent;
        }

        .table thead th {
            font-size: 10px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            font-weight: 800;
            color: rgba(107, 114, 128, 0.90);
            border-bottom: 1px solid rgba(134, 82, 148, 0.10);
            padding-top: 18px;
            padding-bottom: 18px;
        }

        .table tbody td {
            border-top: 1px solid rgba(134, 82, 148, 0.08);
            padding-top: 18px;
            padding-bottom: 18px;
        }

        .table-hover tbody tr:hover {
            background: rgba(134, 82, 148, 0.06);
        }

        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(134, 82, 148, 0.24);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(134, 82, 148, 0.46);
        }

        h1, h2, h3, h4, h5 {
            font-family: 'Inter', sans-serif;
        }

        .text-gray-500, .text-gray-400, .text-gray-600, .text-slate-500, .text-slate-400 {
            color: var(--tailor-muted) !important;
        }

        .text-gray-700, .text-gray-800, .text-slate-700, .text-slate-800, .text-gray-900, .text-slate-900 {
            color: var(--tailor-text) !important;
        }
    </style>
</head>
<body class="flex min-h-screen portal portal-tailor" data-portal="tailor">
