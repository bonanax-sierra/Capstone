<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>About Us</title>

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />

  <!-- Your Sidebar CSS -->
  <link rel="stylesheet" href="css/sidebar.css" />

  <style>
    body {
      background-color: #f1f3f6;
      display: flex;
      min-height: 100vh;
      flex-direction: column;
    }

    .main-content {
      flex: 1;
      margin-left: 250px;
      padding: 40px;
    }

    .card {
      border: none;
      border-radius: 1rem;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.06);
    }

    .section-icon {
      font-size: 2.5rem;
      color: #0d6efd;
    }

    footer {
      background-color: #0d6efd;
      color: #fff;
      padding: 25px 0;
      text-align: center;
      margin-left: 250px;
    }

    footer a {
      color: #fff;
      text-decoration: none;
    }

    footer a:hover {
      text-decoration: underline;
    }

    @media (max-width: 768px) {
      .main-content {
        margin-left: 0;
        padding: 20px;
      }

      footer {
        margin-left: 0;
      }
    }
  </style>
</head>

<body>
  <?php include 'includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="container">
      <div class="card p-5 bg-white">
        <h1 class="text-center fw-bold text-primary mb-3">About Us</h1>
        <p class="lead text-center mb-5">
          Empowering Early Detection of Adolescents' Emotional and Behavioral Risks.
        </p>

        <div class="row text-center">
          <div class="col-md-4 mb-5">
            <div class="section-icon mb-3"><i class="bi bi-bullseye"></i></div>
            <h5 class="fw-semibold">Our Mission</h5>
            <p class="text-muted px-3">To provide a reliable, data-driven system that supports educators and mental health professionals in identifying at-risk adolescents early.</p>
          </div>
          <div class="col-md-4 mb-5">
            <div class="section-icon mb-3"><i class="bi bi-lightbulb"></i></div>
            <h5 class="fw-semibold">Our Vision</h5>
            <p class="text-muted px-3">A future where all adolescents receive timely behavioral support through intelligent digital intervention.</p>
          </div>
          <div class="col-md-4 mb-5">
            <div class="section-icon mb-3"><i class="bi bi-people"></i></div>
            <h5 class="fw-semibold">Our Team</h5>
            <p class="text-muted px-3">We are developers and researchers passionate about mental health and social impact through technology.</p>
          </div>
        </div>

        <hr class="my-5">

        <div class="text-center">
          <h4 class="fw-semibold">Contact Us</h4>
          <p class="text-muted">
            Got feedback or questions?<br>
            Email us at <a href="mailto:support@riskanalytics.org">support@riskanalytics.org</a>
          </p>
        </div>
      </div>
    </div>
  </div>

  <footer>
    <div class="container">
      <p class="mb-1">&copy; <?= date('Y') ?> Risk Analytics. All rights reserved.</p>
      <small>Designed to support adolescent mental well-being through technology.</small>
      <div class="mt-2">
        <a href="index.php">Home</a> |
        <a href="about_us.php">About</a> |
        <a href="contact.php">Contact</a>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
