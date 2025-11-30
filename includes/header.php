<?php
// header.php (Sidebar + Header merged)

// Default page title
$site_name = "Risk Analytics Dashboard";
$page_title = isset($page_title) ? $page_title . " | " . $site_name : $site_name;

$role = $_SESSION['role'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($page_title) ?></title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Global Styles -->
  <style>
    body {
      min-height: 100vh;
      display: flex;
      overflow-x: hidden;
      font-family: "Segoe UI", Tahoma, sans-serif;
      background-color: #e6e6e6ff;
      color: #2c2c2c;
    }

    /* ===========================
        SIDEBAR (NOW INSIDE HEADER)
    ============================*/
    .sidebar {
      width: 250px;
      height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      padding: 2rem 1rem;
      background: #2e3642;
      /* Muted dark navy */
      color: #e8e9eb;
      overflow-y: auto;
      box-shadow: 3px 0 15px rgba(0, 0, 0, 0.15);
    }

    .sidebar h3 {
      font-size: 1.4rem;
      font-weight: 700;
      text-align: center;
      margin-bottom: 2.2rem;
      color: #fff;
    }

    .sidebar .nav-link {
      color: #d3d7dc;
      font-size: 1rem;
      padding: 12px 14px;
      margin-bottom: 5px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: 0.25s;
    }

    .sidebar .nav-link i {
      font-size: 1.2rem;
    }

    .sidebar .nav-link:hover,
    .sidebar .nav-link.active {
      background-color: #3a424e;
      color: #fff;
      transform: translateX(4px);
    }

    .sidebar .logout {
      margin-top: auto;
      padding: 1rem;
    }

    /* ===========================
        MAIN CONTENT
    ============================ */
    .main-content {
      flex: 1;
      padding: 40px;
      margin-left: 250px;
      transition: 0.3s;
    }

    /* DO NOT TOUCH — your SVG map CSS */
    .svg-map-wrapper {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      background-color: #f5f5f5;
    }

    .svg-map-container {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      box-sizing: border-box;
      margin-top: 200px;
    }

    .svg-map-container svg {
      width: 100%;
      max-width: 600px;
      height: auto;
      display: block;
      transform: scale(1.7);
      transform-origin: center center;
      transition: transform 0.5s ease-in-out;

      /* Added border */
      border: 3px solid #2c2c2c;
      /* dark border color */
      border-radius: 12px;
      /* rounded corners */
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      /* subtle shadow for depth */
    }


    svg path {
      transition: transform 0.3s ease, filter 0.3s ease;
      cursor: pointer;
    }

    svg path:hover {
      filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.3));
    }

    #tooltip {
      position: absolute;
      background: rgba(0, 0, 0, 0.8);
      color: white;
      padding: 6px 10px;
      border-radius: 4px;
      font-size: 13px;
      pointer-events: none;
      display: none;
      z-index: 999;
    }
  </style>