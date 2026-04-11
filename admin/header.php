<?php
require_once 'auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal | Silah</title>

    <?php 
        require_once __DIR__ . '/../includes/db_connect.php';
        require_once __DIR__ . '/../includes/theme.php';
        if ($pdo) { echo silah_theme_styles($pdo, 'admin'); }
    ?>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="../css/tailwind.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        :root {
            --admin-bg: #ffffff;
            --admin-primary: #865294;
            --admin-sidebar: #2D1B36;
        }
        
        body {
            background-color: var(--admin-bg);
            font-family: 'Inter', sans-serif;
        }
        
        .glass-card {
            background: var(--card-bg, #ffffff);
            border: 1px solid var(--card-border, rgba(134, 82, 148, 0.10));
            border-radius: 24px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.06);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .glass-card:hover {
            box-shadow: 0 22px 60px rgba(15, 23, 42, 0.10);
            border-color: var(--card-border-hover, rgba(134, 82, 148, 0.22));
            transform: translateY(-1px);
        }
        
        .sidebar-link {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 16px;
            margin: 4px 0;
            border-left: 4px solid transparent;
        }
        
        .sidebar-link:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }

        .sidebar-link.active {
            background: var(--admin-primary);
            color: white !important;
            border-left: 4px solid #fff;
            box-shadow: 0 10px 20px -5px rgba(134, 82, 148, 0.4);
        }

        .stat-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
        }

        .btn-primary {
            background: var(--admin-primary);
            border: none;
            border-radius: 16px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #6d4279;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(134, 82, 148, 0.4);
        }

        .form-control, .form-select {
            border-radius: 16px;
            padding: 0.75rem 1.25rem;
            border: 2px solid #f3f4f6;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 4px rgba(134, 82, 148, 0.1);
        }

        .btn {
            border-radius: 16px;
        }

        .btn-outline {
            border-width: 2px;
        }

        .table {
            --bs-table-bg: transparent;
        }

        .table thead th {
            font-size: 10px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            font-weight: 800;
            color: #94a3b8;
            border-bottom: 1px solid rgba(134, 82, 148, 0.08);
            padding-top: 18px;
            padding-bottom: 18px;
        }

        .table tbody td {
            border-top: 1px solid rgba(134, 82, 148, 0.06);
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
            background: rgba(134, 82, 148, 0.2);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(134, 82, 148, 0.4);
        }
    </style>
</head>
<body class="flex min-h-screen portal portal-admin" data-portal="admin">
