<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin - KarirKu</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap');

    body {
      background-color: #f8f9fa;
      font-family: "Inter", sans-serif;
      font-optical-sizing: auto;
      font-weight: weight;
      font-style: normal;
      /* 1. BERI JARAK DARI ATAS se-tinggi topbar */
      padding-top: 70px;
    }

    .sidebar {
      width: 230px;
      height: calc(100vh - 70px);
      /* 2. Tinggi 100% DIKURANGI tinggi topbar */
      background-color: #fff;
      border-right: 1px solid #dee2e6;
      position: fixed;
      top: 70px;
      /* 3. Mulai di Bawah topbar */
      left: 0;
      padding-top: 20px;
    }

    .sidebar a {
      display: block;
      padding: 10px 20px;
      color: #333;
      text-decoration: none;
      border-radius: 10px;
      margin: 5px 10px;
    }

    .sidebar a.active,
    .sidebar a:hover {
      background-color: #e8f0ff;
      color: #0d6efd;
      font-weight: 600;
    }

    .main-content {
      margin-left: 230px;
      padding: 20px;
    }

    .card {
      border-radius: 15px;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
    }
  </style>
</head>

<body></body>