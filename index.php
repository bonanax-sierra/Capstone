<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Risk Analytics Dashboard</title>
  <link rel="stylesheet" href="css/sidebar.css" />
  <!-- Bootstrap CSS -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet" />
  <!-- Bootstrap Icons -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"
    rel="stylesheet" />

  <style>
    body {
      min-height: 100vh;
      display: flex;
      overflow-x: hidden;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f1f3f6;
    }

    .main-content {
      flex: 1;
      padding: 40px;
      margin-left: 250px;
      /* leaves space for the fixed sidebar on desktop */
      transition: margin-left 0.3s ease-in-out;
    }

    .svg-map-wrapper {
      display: flex;
      justify-content: center;
      /* horizontal center */
      align-items: center;
      /* vertical center */
      height: 100vh;
      /* full height of the viewport */
      background-color: #f5f5f5;
      /* optional background */
    }

    .svg-map-container {
      display: flex;
      justify-content: center;
      /* Center horizontally */
      align-items: center;
      /* Center vertically */
      height: 100vh;
      /* Full height to center on screen */
      box-sizing: border-box;
      margin-top: 150px;
    }

    .svg-map-container svg {
      width: 100%;
      max-width: 600px;
      height: auto;
      display: block;

      transform: scale(1.7);
      transform-origin: center center;
      transition: transform 0.5s ease-in-out;
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

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .main-content {
        margin-left: 0;
        padding: 20px;
      }

      .main-title {
        font-size: 1.5rem;
        text-align: center;
      }

      .svg-map-container {
        height: auto;
        max-height: 400px;
        padding: 15px;
      }
    }

    .transform-hover {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .transform-hover:hover {
      transform: translateY(-5px) scale(1.03);
      box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.25) !important;
    }
  </style>
</head>

<body>
  <?php include 'includes/sidebar.php'; ?>

  <!-- Main Content -->
  <div class="main-content">
    <div class="main-title">
      <h1>Adolescent Risk Dashboard</h1>
    </div>

    <!-- Summary Cards (Bootstrap Compact + Larger Text) -->
    <div class="row g-3 mb-4">
      <?php
      include 'includes/db.php';

      try {
        $query = "
          SELECT TRIM(BOTH '\"' FROM JSON_UNQUOTE(JSON_EXTRACT(current_problems, '$[0]'))) AS problem, COUNT(*) as problem_count
          FROM assessment
          WHERE JSON_LENGTH(current_problems) > 0
          GROUP BY problem
          UNION
          SELECT TRIM(BOTH '\"' FROM JSON_UNQUOTE(JSON_EXTRACT(current_problems, '$[1]'))) AS problem, COUNT(*) as problem_count
          FROM assessment
          WHERE JSON_LENGTH(current_problems) > 1
          GROUP BY problem
          UNION
          SELECT TRIM(BOTH '\"' FROM JSON_UNQUOTE(JSON_EXTRACT(current_problems, '$[2]'))) AS problem, COUNT(*) as problem_count
          FROM assessment
          WHERE JSON_LENGTH(current_problems) > 2
          GROUP BY problem
          ORDER BY problem_count DESC
          LIMIT 3
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $card_styles = [
          "bg-danger bg-gradient",
          "bg-warning bg-gradient",
          "bg-success bg-gradient"
        ];

        $icons = ["bi-exclamation-triangle", "bi-emoji-frown", "bi-heart-pulse"];
        $index = 0;

        foreach ($results as $row) {
          $problem = htmlspecialchars($row['problem'] ?? 'Unknown');
          $count = $row['problem_count'];
      ?>
          <div class="col-md-4 col-sm-6">
            <div class="card text-white <?php echo $card_styles[$index]; ?> shadow-sm border-0 h-100 transform-hover">
              <div class="card-body d-flex flex-column justify-content-center text-center p-3">
                <div class="mb-2">
                  <i class="bi <?php echo $icons[$index]; ?> fs-2"></i>
                </div>
                <h5 class="card-title fw-bold fs-4 mb-2"><?php echo $problem; ?></h5> <!-- ✅ Larger title -->
                <p class="fw-bolder fs-2 mb-0"><?php echo $count; ?></p> <!-- ✅ Larger count -->
                <small class="opacity-75 fs-6">cases</small>
              </div>
            </div>
          </div>
      <?php
          $index++;
        }
      } catch (PDOException $e) {
        echo "<p class='text-danger'>Query failed: " . $e->getMessage() . "</p>";
      }
      ?>
    </div>



    <!-- SVG Map -->
    <div class="row">
      <div class="col-12">
        <h4 class="mb-3">Risk Distribution Map</h4>
        <div class="svg-map-container" id="riskMap">
          <!-- Your SVG goes here -->
          <div id="tooltip">
            <h1>Map: </h1>
          </div>

          <svg
            version="1.1"
            id="svg1"
            width="480"
            height="491"
            viewBox="0 0 480 491"
            sodipodi:docname="sagay.svg"
            inkscape:version="1.4 (86a8ad7, 2024-10-11)"
            xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"
            xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd"
            xmlns="http://www.w3.org/2000/svg"
            xmlns:svg="http://www.w3.org/2000/svg">
            <defs
              id="defs1">
              <inkscape:path-effect
                effect="bspline"
                id="path-effect32"
                is_visible="true"
                lpeversion="1.3"
                weight="33.333333"
                steps="2"
                helper_size="0"
                apply_no_weight="true"
                apply_with_weight="true"
                only_selected="false"
                uniform="false" />
              <inkscape:path-effect
                effect="bspline"
                id="path-effect30"
                is_visible="true"
                lpeversion="1.3"
                weight="33.333333"
                steps="2"
                helper_size="0"
                apply_no_weight="true"
                apply_with_weight="true"
                only_selected="false"
                uniform="false" />
              <inkscape:path-effect
                effect="spiro"
                id="path-effect28"
                is_visible="true"
                lpeversion="1" />
              <inkscape:path-effect
                effect="spiro"
                id="path-effect27"
                is_visible="true"
                lpeversion="1" />
              <inkscape:path-effect
                effect="bspline"
                id="path-effect26"
                is_visible="true"
                lpeversion="1.3"
                weight="33.333333"
                steps="2"
                helper_size="0"
                apply_no_weight="true"
                apply_with_weight="true"
                only_selected="false"
                uniform="false" />
              <inkscape:path-effect
                effect="bspline"
                id="path-effect22"
                is_visible="true"
                lpeversion="1.3"
                weight="33.333333"
                steps="2"
                helper_size="0"
                apply_no_weight="true"
                apply_with_weight="true"
                only_selected="false"
                uniform="false" />
            </defs>
            <sodipodi:namedview
              id="namedview1"
              pagecolor="#ffffff"
              bordercolor="#000000"
              borderopacity="0.25"
              inkscape:showpageshadow="2"
              inkscape:pageopacity="0.0"
              inkscape:pagecheckerboard="0"
              inkscape:deskcolor="#d1d1d1"
              showgrid="false"
              inkscape:zoom="3.1486762"
              inkscape:cx="297.58538"
              inkscape:cy="156.73254"
              inkscape:window-width="1245"
              inkscape:window-height="981"
              inkscape:window-x="665"
              inkscape:window-y="0"
              inkscape:window-maximized="0"
              inkscape:current-layer="g1" />
            <g
              inkscape:groupmode="layer"
              inkscape:label="Image"
              id="g1">
              <path
                style="fill:#7fff2a;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                d="m 117.4055,340.74494 c -13.09522,-1.20769 -9.78427,4.37603 -67.325783,116.31669 l 1.010578,0.56143 C 102.75851,411.82323 162.62735,361.82211 204.36118,316.64754 l -21.65366,-9.3005 c -1.70058,1.54554 -2.42848,2.02129 -5.49473,5.53492 l -3.84265,6.9096 -6.5126,0.44914 -6.96176,7.63548 -3.17126,0.81889 -0.0385,4.79776 -7.17738,1.51518 -3.931,-1.22717 -20.26603,5.32398 c 13.36569,-2.693 -6.0655,1.19094 -7.90611,1.64012 z"
                id="path3"
                sodipodi:nodetypes="ccccccccccccccc" />
              <path
                style="fill:#ff7f2a;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                d="m 124.29303,304.84949 37.39933,-10.43465 8.98291,-3.03173 14.8218,1.23515 0.44914,6.06346 -1.12286,3.48088 -3.37754,5.76748 -2.12449,1.41885 -0.56143,0.33686 -0.92086,1.3883 -1.83016,1.868 -0.84215,1.90887 -0.44914,1.34743 -0.67372,1.12287 -0.84215,0.84214 -0.16842,1.06673 -5.87743,0.0601 -2.55932,2.37329 -3.14966,3.24667 -1.86869,2.35237 -3.38787,0.38901 -0.15879,5.32232 -2.98522,0.40429 -2.96032,1.13649 -3.80741,-0.84382 -2.42379,-0.18204 -2.03712,0.56309 -3.14401,0.83816 -7.386,2.05073 -7.0999,1.95304 -4.55394,0.79399 -2.79436,0.786 -2.86316,1.64577 -6.34982,1.58893 z"
                id="path4"
                sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccc" />
              <path
                style="fill:#ffad76;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                d="m 206.9157,263.64838 17.54473,4.04231 7.24248,2.69487 11.84621,5.16517 -15.72009,17.17981 -22.96257,24.14157 -21.61512,-10.33034 3.31245,-6.51261 -0.50528,-9.60048 1.51585,-1.96502 3.08788,-2.58258 2.41416,-1.79659 3.36859,0.28073 1.40358,0.16842 1.40358,-1.96501 -0.22457,-3.22823 -0.11229,-3.03174 1.40358,-1.88079 3.11595,-2.32995 0.36493,-1.71236 z"
                id="path5"
                sodipodi:nodetypes="ccccccccccccccccccccc" />
              <path
                style="fill:#ff8876;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                d="m 151.53875,231.95576 -27.04198,72.29909 -0.3176,0.79399 34.61773,-10.6394 8.09864,-68.12386 z"
                id="path6"
                sodipodi:nodetypes="cccccc" />
              <path
                style="fill:#ffde76;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                d="m 167.64982,226.36497 -8.05894,67.80628 2.30256,-0.79399 8.94395,-2.58244 c 4.86778,0.59576 8.69533,1.08029 14.6314,1.0855 l 0.006,-0.60711 -0.0397,-0.83369 v -0.51609 l 1.58797,-1.86586 5.75639,-4.60511 4.72421,0.31759 1.07188,-1.42917 -0.43669,-6.39157 1.74676,-2.30256 2.93774,-2.10406 3.29504,-7.78105 1.47816,-0.74017 -2.62944,-2.27697 -10.19506,-1.94526 -2.13013,-0.76109 1.8843,-4.55861 -2.50105,-2.97744 -0.2779,-1.46887 0.91308,-2.85834 0.87339,-2.46135 -0.63519,-2.42166 -0.91308,-1.46887 -0.39699,-5.1212 -1.03218,-1.58797 -5.51819,-3.53323 -3.93023,-0.95278 z"
                id="path7"
                sodipodi:nodetypes="cccccccccccccccccccccccccccccccc" />
              <path
                style="fill:#ffa4a4;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                d="m 193.83146,251.57681 0.89323,0.9301 0.65504,0.85354 -1.73214,3.58456 -0.24281,0.75349 1.07711,0.42868 11.04722,2.22316 2.77272,2.49822 -0.1588,0.43669 17.07067,3.98978 3.45382,-7.60241 0.0397,-0.99248 -1.57153,-3.43904 -4.22173,-4.24217 1.31289,-4.93833 1.66853,-1.2842 0.78718,-0.38798 0.55579,-2.14376 -1.35177,-6.94936 z"
                id="path8"
                sodipodi:nodetypes="cccccccccccccccccccc" />
              <path
                style="fill:#e7a8f8;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                d="m 302.82568,212.15265 -58.75485,63.04237 -18.00982,-8.2807 3.39486,-7.76343 -2.2504,-4.65561 -3.53605,-3.51679 0.5518,-3.02278 0.52524,-1.48765 3.32276,-3.49684 -1.66737,-8.0681 19.44921,-15.24673 15.58395,-13.34235 0.57987,-2.89522 c 23.89785,4.34045 27.2072,5.82255 40.8108,8.73383 z"
                id="path9"
                sodipodi:nodetypes="cccccccccccccc" />
              <path
                style="fill:#a991ec;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                d="m 163.34985,195.50705 -11.61942,35.70123 15.59017,-5.78728 18.15417,3.72292 -0.47639,-3.01714 0.0397,-1.58797 3.29503,-2.30255 -5.3991,-2.77895 0.60795,-2.28852 c 0.57755,-0.33886 0.27374,-0.92939 0.49483,-1.38707 l -1.34097,-4.74065 -1.74677,-2.54075 0.3573,-1.74676 2.38195,-2.89805 -2.58045,-2.93773 -1.49893,-4.57705 -1.08517,-0.57705 -3.89767,-0.39217 c -3.3567,1.17593 -4.27422,1.39844 -6.15869,2.08363 l -2.60336,-1.06191 z"
                id="path10"
                sodipodi:nodetypes="ccccccccccccccccccccc" />
              <path
                style="fill:#91b9ec;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                d="m 193.9307,250.8991 32.19608,-16.51488 1.46887,-7.58255 -0.75429,-0.95278 -1.23747,-1.45202 0.99928,-1.92242 c 1.50857,-0.29113 -1.73003,0.59176 3.05684,-0.27789 l 2.97744,-1.46888 4.00963,-0.19849 0.99248,-0.59549 -3.83099,-6.57022 -1.09172,-3.93022 -1.29023,-2.36212 -2.17606,1.49895 -19.32506,3.21281 -25.50816,4.50969 -0.82928,2.78658 5.61844,2.91889 0.1588,0.31759 -3.69203,2.77894 0.4764,4.16843 4.5654,3.13624 1.19097,1.15128 0.79399,1.94526 0.0794,3.96992 1.74677,4.04932 -1.98496,5.3197 0.79398,1.86586 z"
                id="path11"
                sodipodi:nodetypes="ccccccccccccccccccccccccccccc" />
              <path
                style="fill:#50a3e1;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                d="m 226.86056,233.76617 1.26563,-6.49458 -0.22699,-1.01457 -1.69832,-1.96501 0.67371,-1.32137 3.28438,-0.25065 2.63873,-1.41761 3.93484,-0.29675 1.48298,-0.69979 -0.15257,-0.84954 -3.32831,-5.38235 -0.83193,-2.28884 -0.68394,-2.65176 -1.79658,-2.97559 0.22457,-1.34744 3.81774,-1.74043 1.51587,-2.35802 0.56143,-1.29129 2.91944,-1.12287 19.70626,4.77218 1.2913,0.28071 -0.61758,2.63873 -14.89098,13.01218 -19.06004,14.74581 z"
                id="path12"
                sodipodi:nodetypes="ccccccccccccccccccccccccc" />
              <path
                style="fill:#50dae1;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                d="m 163.5211,194.68499 0.67489,-2.46134 4.04932,-0.87339 0.59549,-0.75429 0.1588,-3.65232 2.42165,-2.89805 0.79398,-1.46887 -0.51609,-1.03218 -4.48601,-2.34226 0.55579,-2.06436 2.58045,-0.39699 2.97744,0.4367 3.75217,-0.30961 7.00632,0.7066 c 0,0 9.64691,1.15128 9.96451,1.15128 0.31759,0 8.81322,0.39699 8.81322,0.39699 l 16.23699,-1.70707 15.1942,-1.20302 -0.30699,4.97445 1.62086,1.12038 0.4435,0.38819 0.23819,0.1588 -2.97462,3.33473 -1.95689,2.80702 -0.7699,1.63131 6.1778,8.42422 -1.66737,3.41413 -3.96992,1.58797 -0.63519,0.63519 0.0794,1.74676 -0.47639,0.95279 -0.79398,0.39699 -44.46313,7.78105 -1.27038,-4.12872 v -0.79399 l -1.82616,-2.14376 v -1.42917 l 2.30255,-2.46135 -0.1588,-1.34977 -1.90556,-2.38195 -1.66737,-5.3594 -3.33473,-0.71459 -2.89804,0.1588 -5.67699,1.70707 z"
                id="path13"
                sodipodi:nodetypes="ccccccccccccccscccccccccccccccccccccccccccccc" />
              <path
                style="fill:#50e1b7;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                d="m 167.68952,152.28622 -8.73383,21.67578 1.27038,1.11158 5.55789,1.27037 0.63519,0.47639 -0.397,1.34978 0.95278,1.03218 0.397,-2.62015 2.69955,-0.47639 2.81864,0.59548 3.85082,-0.35729 0.91308,-1.82616 0.91309,-1.90556 -0.1588,-4.76391 -5.47849,-2.46135 0.0658,-1.48532 1.72069,-1.25393 3.96676,-1.49777 1.78963,-1.28117 -2.7949,-4.09054 -2.49672,-3.01163 -3.36762,0.45313 c -0.80213,-0.21494 -1.32625,-0.093 -2.03148,-0.30796 l -0.82205,-0.46676 z"
                id="path14"
                sodipodi:nodetypes="ccccccccccccccccccccccccc" />
              <path
                style="fill:#16c693;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                d="m 236.60737,124.81436 -27.71006,13.73593 -32.94458,14.29952 5.38975,6.70911 -0.44919,0.786 -2.16151,1.43165 -3.50895,1.34744 -1.57201,1.09479 v 0.61758 l 5.44589,2.58259 0.33686,5.27745 -1.51587,3.14402 0.44915,0.44915 17.62895,1.74044 5.44307,0.16843 21.67409,-1.74044 11.0181,-0.66671 0.37876,-1.40357 -0.0419,-2.79313 -0.8522,-0.41064 -1.05086,0.0193 -0.94563,0.0905 -2.83581,-1.96585 -1.68429,-0.67371 -6.34418,0.67371 -0.44915,-0.0561 -2.75101,-10.10577 1.33339,-3.4107 9.41803,-15.67799 8.95483,-15.66394 z"
                id="path15"
                sodipodi:nodetypes="ccccccccccccccccccccccccccccccc" />
              <path
                style="fill:#8136f6;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                d="m 257.58491,140.24567 -5.05323,19.23385 -1.62415,7.00827 -3.43719,12.54119 -3.26993,9.49982 -3.26582,9.21303 7.18556,1.70707 13.33894,3.25534 2.93375,-6.69954 -1.62085,-2.87362 -4.21094,-2.9321 0.27789,-3.90979 -4.00681,-5.35259 2.26004,-2.49141 5.2006,-6.22996 2.48743,-5.70306 0.84731,-6.3258 0.27789,-6.31218 -0.3244,-5.59126 -2.31618,-1.88664 -3.40733,-3.24172 -2.05073,-3.453 z"
                id="path16"
                sodipodi:nodetypes="ccccccccccccccccccccccc" />
              <path
                style="fill:#8695c5;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                d="m 244.22965,107.90602 -5.8457,15.87806 c 0,0 -13.24195,22.78001 -13.82661,23.76642 -0.25608,0.43207 -5.67269,9.87615 -5.67269,9.87615 l -0.72022,1.57201 2.61605,9.66966 6.18314,-0.63461 4.89526,2.80152 1.78096,-0.32839 1.2913,1.36147 0.11228,2.37205 -0.36492,6.65297 2.27379,1.51586 -0.30879,1.0948 -2.82126,2.86344 -2.58609,4.14283 6.34252,8.15156 2.69487,-1.26921 6.64417,-18.29586 10.35841,-39.86166 0.72987,-4.09846 -3.56509,-8.47761 -3.25631,-4.1546 -2.35801,-3.03173 -1.90887,-6.84947 z"
                id="path17"
                sodipodi:nodetypes="ccsccccccccccccccccccccccc" />
              <path
                style="fill:#5267ab;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                d="m 168.26111,151.53045 13.86737,-33.46134 5.38974,-15.55166 5.38975,-19.930831 3.56349,-7.038996 5.43879,-7.542852 2.02466,-3.930224 -0.99248,-1.111578 -2.06436,-0.119098 -0.95278,-0.595488 4.84331,-3.850825 1.70706,0.436692 2.14376,1.786465 3.01714,0.793984 19.37322,3.056841 2.38196,1.032179 7.70165,2.104059 0.99248,1.429172 0.0397,0.793985 -1.86587,2.659847 -2.23304,0.947319 -1.41205,2.363657 -1.2227,4.614542 -1.44045,8.982909 -6.17858,4.359903 -9.65662,3.424734 -1.6843,-1.347437 -2.75101,-1.796581 -7.24247,-5.165173 -1.01058,0.617575 0.72986,1.179007 8.36533,6.34418 3.93003,2.021154 3.14401,-0.16843 -0.16842,-0.89829 3.32889,-1.221529 1.05032,-0.575054 1.85272,-0.561431 4.69958,-2.705674 2.51,-1.725648 0.98732,-1.575998 2.75219,-13.608737 1.45008,-1.666199 -0.44067,5.449879 0.89829,0.617575 3.59316,0.617575 1.45972,1.965011 1.51587,4.323025 0.28071,2.021155 -2.33758,13.635979 -1.46371,5.3733 -6.05101,15.27095 -7.66437,3.61642 -16.11309,8.02847 -5.05289,2.4703 -25.54514,11.45321 -7.63548,3.20016 -0.72986,0.0842 -2.80716,0.25264 -1.57201,-0.40698 z"
                id="path18"
                sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc" />
              <path
                style="fill:#7a52ab;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                d="m 240.31126,77.791194 0.34649,-3.457623 5.58624,-3.031731 c 0,0 4.60374,-1.684296 5.16517,-1.796582 0.56144,-0.112287 7.4109,3.480877 7.4109,3.480877 l 9.20749,1.796582 0.89829,16.842954 0.33686,18.976399 -0.11229,5.27746 -1.23515,3.81773 -0.89829,9.76891 0.56143,7.74776 0.33686,5.61432 -3.08788,4.09845 -3.56508,-2.72294 -1.45973,-2.13344 -1.74044,-3.03173 0.0281,-0.89829 0.53335,-3.03174 -2.44223,-6.09153 -4.0423,-6.4284 -1.0948,-1.34743 -1.76851,-2.55452 -1.68429,-5.61431 -0.21893,-0.84614 -2.58823,-4.54361 3.14402,-16.842953 -0.007,-3.064683 -1.82616,-5.438794 -0.99248,-1.349773 -2.00024,-1.243472 -0.22292,-0.807608 -0.58585,-0.755111 -0.19369,1.439631 -1.42235,-0.278716 z"
                id="path19"
                sodipodi:nodetypes="cccscccccccccccccccccccccccccccccccc" />
              <path
                style="fill:#737fa7;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                d="m 266.54059,149.11028 -0.1588,10.00421 -0.70096,5.87948 -3.03076,8.33284 -6.70635,7.77142 3.7686,4.93233 -0.15879,3.81113 4.10945,3.10053 1.52784,2.61616 v 1.11158 l -2.77895,5.95488 13.33894,2.54074 16.19728,3.49354 11.59217,2.93775 19.61142,-21.67578 -10.48059,-9.68661 5.63729,-7.38406 0.63518,-2.06436 -0.96241,-1.36422 -10.39156,-0.93833 -9.60722,-1.66737 -9.8454,-2.77895 -4.883,-2.4812 -5.03053,-2.784 -2.87526,0.37195 -4.23702,-1.57659 -1.71223,-5.08268 -1.19097,-1.94525 -1.37868,-2.0235 z"
                id="path20"
                sodipodi:nodetypes="cccccccccccccccccccccccccccccc" />
              <g
                id="g35">
                <path
                  style="fill:#008fe5;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                  d="m 268.81355,74.67043 1.12286,28.8576 0.11229,11.00406 -0.22457,2.4703 -1.23515,3.03173 -0.67372,10.77949 0.89829,12.23921 -3.16445,3.86425 0.27108,0.80644 3.23023,2.17878 2.18958,7.29861 4.21074,1.34744 2.80716,-0.33686 0.786,-1.68429 1.6843,-6.06347 0.89829,-3.93002 2.24573,-2.4703 0.2189,-7.25211 0.0794,-0.17569 4.19312,2.03808 1.79658,0.33686 7.74776,-5.8389 0.78601,-5.16517 2.69487,-1.12286 11.5655,-1.45972 4.49145,-2.80716 5.05289,-4.71603 2.24572,-2.24573 4.82832,-5.50203 -2.91945,-4.1546 -4.82831,-3.14401 -13.81123,-9.993491 -5.7266,-2.582584 -9.76891,-4.042309 -6.84947,-3.593164 -5.27746,-3.144018 -4.26688,-1.796581 c -1.1871,-4.68905 -5.16517,-1.87144 -7.4109,-3.031733 z"
                  id="path21"
                  sodipodi:nodetypes="ccccccccccccccccccccccccccccccccccccccc" />
                <path
                  style="fill:#008fe5;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                  d="m 311.21545,70.108829 c -0.97925,0 -2.93774,0 -3.70526,1.402734 -0.76751,1.402734 -0.34407,4.208037 1.44243,5.491669 1.7865,1.283633 4.93592,1.045442 6.44449,-0.370496 1.50856,-1.415937 1.37623,-4.009623 0.569,-5.29323 -0.80723,-1.283608 -2.28931,-1.257142 -3.03036,-1.243908 -0.74105,0.01323 -0.74105,0.01323 -1.7203,0.01323 z"
                  id="path22"
                  inkscape:path-effect="#path-effect22"
                  inkscape:original-d="m 312.1947,70.108829 c -1.9585,0 -3.91699,0 -5.87549,0 0.42347,2.805468 0.84691,5.610771 1.27038,8.416236 3.14953,-0.2382 6.29895,-0.476391 9.44841,-0.714586 -0.13233,-2.59368 -0.26466,-5.187366 -0.39699,-7.781048 -1.48213,0.02647 -2.96421,0.05293 -4.44631,0.0794 z"
                  sodipodi:nodetypes="sccccss" />
              </g>
              <path
                style="fill:#005b92;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                d="m 298.4696,133.64004 -7.42335,5.70733 -1.23515,0.44915 -5.50203,-2.66081 0.56143,6.59083 -0.44915,0.78601 -2.04158,2.12662 -2.78673,10.44945 -0.63743,1.69169 8.72205,4.37177 14.14808,3.70545 15.15866,1.71237 5.53011,-5.69853 1.23514,-0.78601 10.18999,-4.77217 1.57201,-1.57201 0.33686,-4.60374 2.71083,-0.99131 7.77831,1.90089 1.35074,-5.19242 -4.51237,-6.73168 -0.59033,-2.45984 -2.13344,-1.79658 -5.83889,-0.33686 -0.44915,1.34743 -8.30919,2.24573 -4.71603,3.14402 -4.71602,0.89829 z"
                id="path23"
                sodipodi:nodetypes="ccccccccccccccccccccccccccccc" />
              <path
                style="fill:#008a92;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                d="m 323.78687,189.92109 -10.18626,-9.79408 5.31488,-7.38186 0.50447,-1.5621 -0.78436,-1.43797 -0.59349,-1.30891 2.80702,-2.89041 2.50373,-2.51102 10.27942,-4.79363 2.38195,-1.98496 0.41543,-4.75029 2.11253,-0.77954 7.63504,1.7981 0.79399,0.0794 1.11157,-4.92271 0.2382,-1.03218 -3.01714,-4.28751 -1.42834,-2.60653 -0.49085,-2.26884 1.26956,-0.50529 0.51491,0.58868 17.99937,15.66956 17.94405,13.49774 0.95278,0.1588 0.0794,0.87338 z"
                id="path24"
                sodipodi:nodetypes="cccccccccccccccccccccccccc" />
              <g
                id="g34">
                <path
                  style="fill:#009273;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                  d="m 330.05935,110.52264 -7.46346,8.09864 -4.7242,4.64481 -4.48602,2.58045 -11.75097,1.54827 -2.38195,1.11158 -0.55579,3.37443 0.35729,1.42917 4.92271,2.73925 4.40661,2.22316 4.20812,2.38195 3.93022,1.94526 4.60511,-0.87338 4.44632,-3.09654 8.09864,-2.18346 0.55579,-1.34977 6.55037,0.35729 2.10406,1.66737 2.02466,-0.59549 7.97954,-4.20812 0.83368,0.51609 3.29504,-1.03218 3.49353,-0.11909 4.24782,-0.2382 4.04932,-1.15128 2.77894,-0.15879 4.00963,0.23819 1.50857,-0.1191 5.62513,1.00961 -0.16843,-0.78601 0.11229,-1.45972 0.95443,-2.24572 0.39301,-1.17902 2.52644,-0.72985 1.96501,-0.28072 1.2913,-3.14402 -0.22458,-3.2563 -3.36859,-6.2319 -4.1546,-4.60374 -2.07729,-2.02115 -2.97559,0.16843 -4.21074,0.95443 -1.57201,0.72986 c -0.38001,-0.87576 -2.03987,-0.71115 -3.03173,-1.17901 l -0.67372,-1.51586 -2.4703,-6.512609 -3.59316,-4.210739 -0.28072,-1.908868 -1.23515,-0.561432 -1.29129,0.842148 -0.28072,1.122863 -1.01057,0.617575 -1.57201,0.224573 -3.31245,-1.23515 -2.13344,0.786005 -1.06672,1.347436 -0.78601,2.694873 -0.28071,4.266885 -1.17901,-0.44915 -1.62815,1.23515 -1.62815,1.74044 -7.74776,4.88446 -5.72661,4.65988 z"
                  id="path25"
                  sodipodi:nodetypes="cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc" />
                <path
                  style="fill:#009273;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                  d="m 332.25535,106.21354 c -0.82344,0.73922 -0.86087,2.03051 -0.0561,2.48901 0.80473,0.4585 2.45157,0.0842 3.275,-0.65501 0.82343,-0.73923 0.82343,-1.84336 0.0187,-2.30186 -0.80474,-0.4585 -2.41414,-0.27136 -3.23757,0.46786 z"
                  id="path26"
                  inkscape:path-effect="#path-effect26"
                  inkscape:original-d="m 331.46934,105.66147 c -0.0374,1.29129 -0.0749,2.58258 -0.11229,3.87388 1.6469,-0.3743 3.29374,-0.74858 4.9406,-1.12287 0,-1.10417 0,-2.2083 0,-3.31245 -1.60947,0.18715 -3.21887,0.37429 -4.82831,0.56144 z"
                  transform="matrix(1.0825949,0,0,1.0589252,-27.294021,-6.1765335)" />
                <path
                  style="fill:#009273;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                  d="m 377.73132,84.102485 c -1.8813,-0.196702 -3.75166,0.405801 -5.50203,1.122863 -2.28821,0.937396 -4.4901,2.08539 -6.56875,3.424735 l -2.80716,3.144018 0.50529,2.751016 c 0.95924,0.124444 1.9465,0.02765 2.8633,-0.280716 1.77523,-0.597103 3.25578,-2.014203 3.93002,-3.761594 l 4.88446,-3.256304 c 0.92915,-0.722998 2.14105,-1.072187 3.31245,-0.954434 0.20613,0.02072 0.41779,0.05485 0.61757,0 0.14365,-0.03944 0.27365,-0.124606 0.37098,-0.23738 0.0973,-0.112774 0.16219,-0.25244 0.19048,-0.398695 0.0566,-0.292509 -0.0354,-0.602721 -0.21359,-0.8415 -0.17818,-0.238779 -0.43601,-0.410166 -0.71301,-0.519887 -0.27699,-0.109722 -0.57369,-0.16114 -0.87001,-0.192122 z"
                  id="path27"
                  inkscape:path-effect="#path-effect27"
                  inkscape:original-d="m 377.73132,84.102485 c -0.41172,-0.729861 -3.68673,0.711147 -5.50203,1.122863 -1.8153,0.411717 -8.79577,4.585027 -6.56875,3.424735 -2.22702,1.216435 -3.72417,4.154595 -2.80716,3.144018 -0.86086,0.954434 0.655,3.668021 0.50529,2.751016 0.14971,0.860862 1.90886,-0.205859 2.8633,-0.280716 0.95443,-0.07486 5.24003,-4.996743 3.93002,-3.761594 1.31001,-1.179006 6.4939,-4.341739 4.88446,-3.256304 1.55329,-1.029292 2.18958,-0.617575 3.31245,-0.954434 1.12286,-0.336859 0.41171,0 0.61757,0 0.20586,0 -0.82343,-1.459723 -1.23515,-2.189584 z" />
                <path
                  style="fill:#009273;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                  d="m 372.22929,89.941376 c 1.38764,-0.750403 2.92636,-1.205765 4.49145,-1.40358 2.9678,-0.375106 6.09241,0.206202 8.58991,1.852725 1.39695,0.920962 2.60766,2.201972 3.17439,3.776281 0.28337,0.787155 0.39995,1.638464 0.29987,2.469061 -0.10008,0.830597 -0.42076,1.638714 -0.94782,2.288422 -0.44346,0.546655 -1.02962,0.976508 -1.68429,1.235145 l -1.6843,-0.11228 1.57201,1.06672 0.11229,1.40358 -1.34744,1.34743 c -0.31023,0.55436 -0.80688,1.00223 -1.39026,1.25369 -0.58338,0.25145 -1.25,0.305 -1.86604,0.14989 -0.409,-0.10298 -0.79508,-0.29602 -1.12287,-0.56143 0.02,-0.23489 -0.0403,-0.47586 -0.16843,-0.67372 -0.13607,-0.21009 -0.34491,-0.3683 -0.57693,-0.46222 -0.23202,-0.0939 -0.48634,-0.12574 -0.73634,-0.11342 -0.5,0.0246 -0.97362,0.2196 -1.43774,0.40721 -0.92587,0.37426 -1.87183,0.73292 -2.86408,0.84583 -0.99224,0.11291 -2.04871,-0.0425 -2.86253,-0.62125 -0.46692,-0.33208 -0.83515,-0.78899 -1.12027,-1.28598 -0.28511,-0.49699 -0.491,-1.03515 -0.67631,-1.57732 -0.44378,-1.298436 -0.77613,-2.634168 -1.01058,-3.986171 -0.2331,-1.344213 -0.36307,-2.76353 0.11229,-4.042309 0.26868,-0.722789 0.72273,-1.369015 1.27231,-1.909914 0.54959,-0.540899 1.19342,-0.979589 1.87171,-1.34639 z"
                  id="path28"
                  inkscape:path-effect="#path-effect28"
                  inkscape:original-d="m 372.22929,89.941376 c 1.048,-1.085435 3.03173,-0.973149 4.49145,-1.40358 1.45973,-0.430431 5.72661,1.23515 8.58991,1.852725 2.8633,0.617575 1.6843,5.689176 2.52644,8.533764 0.84215,2.844585 -2.24572,1.665585 -1.68429,1.235145 -0.56143,0.48658 -2.22702,-0.14971 -1.6843,-0.11228 -0.48657,0.0187 2.0773,1.42229 1.57201,1.06672 0.50529,0.41172 0.131,1.85272 0.11229,1.40358 -0.0374,0.44914 -1.75916,1.79658 -1.34744,1.34743 -0.35557,0.44915 -2.11473,0.93572 -3.2563,1.40358 -1.14158,0.46786 -1.44101,-0.72986 -1.12287,-0.56143 -0.262,-0.11228 -0.131,-0.46786 -0.16843,-0.67372 -0.0374,-0.20585 -1.83401,-0.0748 -2.75101,-0.16843 -0.91701,-0.0936 -3.78031,0.18715 -5.72661,0.22458 -1.94629,0.0374 -1.19772,-1.9463 -1.79658,-2.8633 -0.59886,-0.91701 -0.63629,-2.638734 -1.01058,-3.986171 -0.37428,-1.347436 0.0374,-2.713587 0.11229,-4.042309 0.0749,-1.328722 2.09601,-2.170869 3.14402,-3.256304 z" />
              </g>
              <path
                style="fill:#cc2626;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                d="m 345.22445,136.80353 20.4054,17.86465 16.03849,11.98916 0.71458,-0.1588 v 1.19098 l 15.4827,-5.31969 1.19098,-1.50857 -1.11158,-4.84331 -3.09654,-1.58797 -1.11158,-1.11158 0.39699,-4.36691 0.79399,0.87338 h 1.74676 l 2.14376,-1.27037 2.22316,-1.74677 1.82616,-2.14376 0.55579,-2.30255 -1.90556,-1.82617 -1.27037,-0.23819 -2.30256,1.03218 -1.03218,0.95278 0.0794,-3.49353 -1.82617,-2.06436 -0.87338,-0.3176 2.54075,-1.42917 1.03218,-1.11158 -0.95278,-1.50857 -4.68451,-0.87338 -0.95278,1.27037 0.2382,1.11158 -8.33684,-4.20811 -0.0794,1.50857 v 0.55578 l -4.44631,-0.55578 -9.21022,-0.397 -4.52571,1.27038 -7.70165,0.39699 -3.57293,1.11158 -1.03218,-0.39699 z"
                id="path29" />
              <g
                id="g33">
                <path
                  style="fill:#cc5526;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                  d="m 421.16743,37.110643 c -1.51587,1.179007 -1.59072,3.424734 -0.11226,4.547598 1.47847,1.122863 4.51014,1.122863 6.02601,-0.05617 1.51586,-1.179031 1.51586,-3.536997 0.0374,-4.65986 -1.47846,-1.122863 -4.43528,-1.010578 -5.95114,0.168429 z"
                  id="path30"
                  inkscape:path-effect="#path-effect30"
                  inkscape:original-d="m 419.72642,36.043922 c -0.0749,2.245727 -0.14972,4.491455 -0.22457,6.737182 3.03179,0 6.06346,0 9.09519,0 0,-2.358061 0,-4.716027 0,-7.074041 -2.95693,0.112289 -5.91375,0.224573 -8.87062,0.336859 z" />
                <path
                  style="fill:#cc5526;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                  d="m 426.35131,45.812836 2.80716,1.908868 6.6249,5.951177 1.01058,1.347436 -0.22458,1.908868 -6.40032,5.389746 h -1.01058 l -0.89829,-2.245728 -0.89829,-0.89829 -2.35801,-0.673719 -1.34744,-5.052886 -2.24572,-2.582586 0.44914,-1.010577 3.03173,-1.010578 -0.56143,-1.572009 z"
                  id="path31" />
                <path
                  style="fill:#cc5526;fill-opacity:1;stroke:#ffffff;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                  d="m 450.36188,48.357993 c 2.48902,0.598862 5.96989,2.844586 8.00977,4.809615 2.03987,1.965028 2.63872,3.649291 3.27502,6.718496 0.63629,3.069204 1.31,7.523142 1.40357,10.19931 0.0936,2.676168 -0.393,3.574438 -0.71115,4.416593 -0.31815,0.842156 -0.46786,1.628147 -1.96504,1.571993 -1.49718,-0.05615 -4.34171,-0.954426 -6.83074,-1.927585 -2.48904,-0.973159 -4.62244,-2.021143 -6.04474,-2.751008 -1.4223,-0.729865 -2.13343,-1.141572 -2.60129,-1.253856 -0.46787,-0.112285 -0.69243,0.07485 -0.89829,0.580149 -0.20587,0.505297 -0.39301,1.328714 -1.19774,1.946294 -0.80473,0.617579 -2.227,1.029289 -3.5183,1.235147 -1.29131,0.205858 -2.45157,0.205858 -3.29373,-0.280725 -0.84215,-0.486583 -1.36615,-1.459711 -1.08542,-2.339298 0.28073,-0.879586 1.36614,-1.665572 2.17087,-2.320581 0.80472,-0.655009 1.32871,-1.179 1.572,-2.938184 0.24329,-1.759185 0.20586,-4.753428 0.35558,-6.755879 0.14972,-2.002451 0.48657,-3.013007 1.49716,-4.416605 1.0106,-1.403598 2.69486,-3.200144 4.2856,-4.622449 1.59074,-1.422305 3.08786,-2.470289 5.57687,-1.871427 z"
                  id="path32"
                  inkscape:path-effect="#path-effect32"
                  inkscape:original-d="m 449.37002,46.711126 c 3.48088,2.245728 6.96175,4.491455 10.44263,6.737182 0.59887,1.684329 1.19772,3.368591 1.79658,5.052886 0.67373,4.454115 1.34744,8.908052 2.02116,13.362077 -0.48659,0.898309 -0.97315,1.796582 -1.45973,2.694873 -0.14971,0.78602 -0.29943,1.572009 -0.44914,2.358014 -2.84465,-0.898309 -5.68918,-1.796582 -8.53376,-2.694873 -2.13349,-1.048027 -4.26689,-2.096012 -6.40033,-3.144018 -0.71116,-0.411725 -1.42229,-0.823433 -2.13344,-1.23515 -0.22458,0.187148 -0.44914,0.374288 -0.67372,0.561432 -0.18714,0.82345 -0.37428,1.646866 -0.56143,2.4703 -1.42232,0.411725 -2.84459,0.823433 -4.26688,1.23515 -1.16032,0 -2.32058,0 -3.48088,0 -0.52401,-0.973168 -1.048,-1.946297 -1.57201,-2.919446 1.08546,-0.78602 2.17087,-1.572009 3.25631,-2.358013 0.52401,-0.524014 1.048,-1.048006 1.57201,-1.572009 -0.0374,-2.994363 -0.0749,-5.988606 -0.11229,-8.982909 0.33687,-1.010598 0.67372,-2.021155 1.01058,-3.031732 1.68433,-1.796618 3.36859,-3.593164 5.05288,-5.389745 1.49719,-1.048027 2.99431,-2.096013 4.49146,-3.144019 z" />
              </g>
              <g
                inkscape:label="Clip"
                id="g36">
                <text
                  xml:space="preserve"
                  style="font-style:italic;font-variant:normal;font-weight:bold;font-stretch:normal;font-size:3.33333px;font-family:sans-serif;-inkscape-font-specification:'sans-serif Bold Italic';text-align:start;writing-mode:lr-tb;direction:ltr;text-anchor:start;white-space:pre;inline-size:33.3764;display:inline;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                  x="105.10003"
                  y="373.689"
                  id="text35"
                  transform="translate(16.925457,-12.576073)">
                  <tspan
                    x="105.10003"
                    y="373.689"
                    id="tspan1">Puey</tspan>
                </text>
              </g>
              <text
                xml:space="preserve"
                style="font-style:italic;font-variant:normal;font-weight:bold;font-stretch:normal;font-size:3.33333px;font-family:sans-serif;-inkscape-font-specification:'sans-serif Bold Italic';text-align:start;writing-mode:lr-tb;direction:ltr;text-anchor:start;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                x="255.54239"
                y="-150.4698"
                id="text37"
                transform="rotate(90)">
                <tspan
                  sodipodi:role="line"
                  id="tspan37"
                  x="255.54239"
                  y="-150.4698">Campo Santiago</tspan>
              </text>
              <text
                xml:space="preserve"
                style="font-style:italic;font-variant:normal;font-weight:bold;font-stretch:normal;font-size:3.33333px;font-family:sans-serif;-inkscape-font-specification:'sans-serif Bold Italic';text-align:start;writing-mode:lr-tb;direction:ltr;text-anchor:start;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                x="202.42477"
                y="289.33261"
                id="text38">
                <tspan
                  sodipodi:role="line"
                  id="tspan38"
                  x="202.42477"
                  y="289.33261">Makiling</tspan>
              </text>
              <g
                id="g4"
                style="overflow:hidden;fill:currentColor"
                transform="matrix(0.01579114,0,0,0.01232821,223.40174,57.269971)">
                <path
                  d="m 537.42933,24.746667 c -9.38666,-0.512 -18.09066,-0.853333 -25.77066,-0.853334 -205.99467,0 -380.416,153.770667 -405.67467,357.546667 -12.458667,100.01067 12.62933,200.704 70.656,283.648 57.856,82.60267 143.53067,140.8 241.32267,163.84 L 428.032,831.31733 512,976.384 595.968,831.31733 606.03733,828.928 c 89.088,-20.992 169.472,-71.85067 226.47467,-143.53067 57.17333,-71.85066 88.74667,-161.792 88.74667,-253.44 0,-215.04 -168.61867,-393.898664 -383.82934,-407.210663 z"
                  fill="#070f1b"
                  id="path1" />
                <path
                  d="M 538.79467,0.853333 C 529.92,0.341333 520.704,0 511.65867,0 293.71733,0 109.056,162.64533 82.261333,378.53867 c -27.306667,219.30666 116.565337,423.424 330.239997,473.6 L 475.136,960.34133 512,1024 548.864,960.34133 611.49867,852.13867 C 805.888,806.4 945.152,632.832 945.152,431.95733 945.152,204.288 766.80533,15.018667 538.79467,0.853333 Z M 606.03733,828.928 595.968,831.31733 512,976.384 428.032,831.31733 417.96267,828.928 C 320.17067,805.888 234.496,747.86133 176.64,665.088 118.61333,582.144 93.525333,481.45067 105.984,381.44 131.24267,177.664 305.664,23.893333 511.65867,23.893333 c 7.68,0 16.384,0.341333 25.77066,0.853334 215.21067,13.312 383.82934,192.341333 383.82934,407.210663 0,91.47734 -31.57334,181.58934 -88.74667,253.44 C 775.50933,756.90667 695.12533,807.936 606.03733,828.928 Z"
                  fill="#9b7bff"
                  id="path2" />
                <path
                  d="m 767.488,313.17333 c -12.97067,-6.48533 -217.94133,-99.328 -217.94133,-99.328 0,0 -21.504,-10.92266 -32.42667,-10.92266 -10.92267,0 -32.42667,10.92266 -32.42667,10.92266 0,0 -205.14133,92.84267 -218.112,99.328 -12.97066,6.48534 0,12.97067 0,12.97067 0,0 174.93334,79.70133 207.18934,92.84267 25.94133,10.92266 32.42666,10.92266 43.17866,10.92266 10.92267,0 17.23734,0 43.17867,-10.92266 21.504,-10.92267 112.128,-51.712 166.22933,-75.60534 v 69.12 l -17.23733,17.23734 23.72267,23.72266 23.72266,-23.72266 -17.06666,-17.23734 V 339.11467 C 756.736,332.62933 767.488,326.144 767.488,326.144 c 0,0 12.8,-6.48533 0,-12.97067 z"
                  fill="#9b7bff"
                  id="path3-8" />
                <path
                  d="m 517.12,474.624 c -10.92267,0 -17.23733,0 -43.17867,-10.92267 C 458.752,457.216 407.04,433.49333 359.59467,411.98933 v 157.52534 c 47.616,21.504 99.328,45.39733 114.34666,51.712 25.94134,10.92266 32.42667,10.92266 43.17867,10.92266 10.92267,0 17.23733,0 43.17867,-10.92266 15.18933,-6.48534 66.90133,-30.208 114.34666,-51.712 V 411.98933 c -47.616,21.504 -99.328,45.39734 -114.34666,51.712 C 534.35733,476.84267 527.872,474.624 517.12,474.624 Z"
                  fill="#9b7bff"
                  id="path4-2" />
              </g>
              <text
                xml:space="preserve"
                style="font-style:italic;font-variant:normal;font-weight:bold;font-stretch:normal;font-size:3.33333px;font-family:sans-serif;-inkscape-font-specification:'sans-serif Bold Italic';text-align:start;writing-mode:lr-tb;direction:ltr;text-anchor:start;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                x="204.90167"
                y="102.19281"
                id="text39">
                <tspan
                  sodipodi:role="line"
                  id="tspan39"
                  x="204.90167"
                  y="102.19281">Himuga-an</tspan>
                <tspan
                  sodipodi:role="line"
                  x="204.90167"
                  y="106.35947"
                  id="tspan40">Baybay</tspan>
              </text>
              <text
                xml:space="preserve"
                style="font-style:italic;font-variant:normal;font-weight:bold;font-stretch:normal;font-size:3.33333333px;font-family:sans-serif;-inkscape-font-specification:'sans-serif Bold Italic';text-align:start;writing-mode:lr-tb;direction:ltr;text-anchor:start;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                x="250.5815"
                y="97.024902"
                id="text41">
                <tspan
                  sodipodi:role="line"
                  id="tspan41"
                  x="250.5815"
                  y="97.024902">Old</tspan>
                <tspan
                  sodipodi:role="line"
                  x="250.5815"
                  y="100.35824"
                  id="tspan42">Sagay</tspan>
              </text>
              <text
                xml:space="preserve"
                style="font-style:italic;font-variant:normal;font-weight:bold;font-stretch:normal;font-size:3.33333px;font-family:sans-serif;-inkscape-font-specification:'sans-serif Bold Italic';text-align:start;writing-mode:lr-tb;direction:ltr;text-anchor:start;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                x="127.6727"
                y="320.29333"
                id="text43">
                <tspan
                  sodipodi:role="line"
                  id="tspan43"
                  x="127.6727"
                  y="320.29333">Colonia Divina</tspan>
              </text>
              <text
                xml:space="preserve"
                style="font-style:italic;font-variant:normal;font-weight:bold;font-stretch:normal;font-size:3.33333px;font-family:sans-serif;-inkscape-font-specification:'sans-serif Bold Italic';text-align:start;writing-mode:lr-tb;direction:ltr;text-anchor:start;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                x="194.685"
                y="155.62096"
                id="text44">
                <tspan
                  sodipodi:role="line"
                  id="tspan44"
                  x="194.685"
                  y="155.62096">Paraiso</tspan>
              </text>
              <text
                xml:space="preserve"
                style="font-style:italic;font-variant:normal;font-weight:bold;font-stretch:normal;font-size:3.33333px;font-family:sans-serif;-inkscape-font-specification:'sans-serif Bold Italic';text-align:start;writing-mode:lr-tb;direction:ltr;text-anchor:start;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                x="156.45044"
                y="-168.34624"
                id="text45"
                transform="rotate(90)">
                <tspan
                  sodipodi:role="line"
                  id="tspan45"
                  x="156.45044"
                  y="-168.34624">Fabrica</tspan>
              </text>
              <text
                xml:space="preserve"
                style="font-style:italic;font-variant:normal;font-weight:bold;font-stretch:normal;font-size:3.33333px;font-family:sans-serif;-inkscape-font-specification:'sans-serif Bold Italic';text-align:start;writing-mode:lr-tb;direction:ltr;text-anchor:start;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                x="171.78166"
                y="262.61154"
                id="text46">
                <tspan
                  sodipodi:role="line"
                  id="tspan46"
                  x="171.78166"
                  y="262.61154">Baviera</tspan>
              </text>
              <text
                xml:space="preserve"
                style="font-style:italic;font-variant:normal;font-weight:bold;font-stretch:normal;font-size:3.33333px;font-family:sans-serif;-inkscape-font-specification:'sans-serif Bold Italic';text-align:start;writing-mode:lr-tb;direction:ltr;text-anchor:start;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                x="161.02005"
                y="209.9295"
                id="text47">
                <tspan
                  sodipodi:role="line"
                  id="tspan47"
                  x="161.02005"
                  y="209.9295">Tadlong</tspan>
              </text>
              <text
                xml:space="preserve"
                style="font-style:italic;font-variant:normal;font-weight:bold;font-stretch:normal;font-size:3.33333px;font-family:sans-serif;-inkscape-font-specification:'sans-serif Bold Italic';text-align:start;writing-mode:lr-tb;direction:ltr;text-anchor:start;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                x="191.82664"
                y="188.80951"
                id="text48">
                <tspan
                  sodipodi:role="line"
                  id="tspan48"
                  x="191.82664"
                  y="188.80951">Malubon</tspan>
              </text>
              <text
                xml:space="preserve"
                style="font-style:italic;font-variant:normal;font-weight:bold;font-stretch:normal;font-size:3.33333px;font-family:sans-serif;-inkscape-font-specification:'sans-serif Bold Italic';text-align:start;writing-mode:lr-tb;direction:ltr;text-anchor:start;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                x="280.79141"
                y="108.09941"
                id="text49">
                <tspan
                  sodipodi:role="line"
                  id="tspan49"
                  x="280.79141"
                  y="108.09941">Taba-ao</tspan>
              </text>
              <text
                xml:space="preserve"
                style="font-style:italic;font-variant:normal;font-weight:bold;font-stretch:normal;font-size:3.33333px;font-family:sans-serif;-inkscape-font-specification:'sans-serif Bold Italic';text-align:start;writing-mode:lr-tb;direction:ltr;text-anchor:start;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                x="340.61932"
                y="121.32083"
                id="text50">
                <tspan
                  sodipodi:role="line"
                  id="tspan50"
                  x="340.61932"
                  y="121.32083">Bulanon</tspan>
              </text>
              <text
                xml:space="preserve"
                style="font-style:italic;font-variant:normal;font-weight:bold;font-stretch:normal;font-size:3.33333px;font-family:sans-serif;-inkscape-font-specification:'sans-serif Bold Italic';text-align:start;writing-mode:lr-tb;direction:ltr;text-anchor:start;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                x="371.42593"
                y="144.50517"
                id="text51">
                <tspan
                  sodipodi:role="line"
                  id="tspan51"
                  x="371.42593"
                  y="144.50517">Vito</tspan>
              </text>
              <text
                xml:space="preserve"
                style="font-style:italic;font-variant:normal;font-weight:bold;font-stretch:normal;font-size:3.33333px;font-family:sans-serif;-inkscape-font-specification:'sans-serif Bold Italic';text-align:start;writing-mode:lr-tb;direction:ltr;text-anchor:start;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                x="326.32761"
                y="173.24741"
                id="text52">
                <tspan
                  sodipodi:role="line"
                  id="tspan52"
                  x="326.32761"
                  y="173.24741">Andres Bonifacio</tspan>
              </text>
              <text
                xml:space="preserve"
                style="font-style:italic;font-variant:normal;font-weight:bold;font-stretch:normal;font-size:3.33333px;font-family:sans-serif;-inkscape-font-specification:'sans-serif Bold Italic';text-align:start;writing-mode:lr-tb;direction:ltr;text-anchor:start;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                x="299.33215"
                y="149.42789"
                id="text53">
                <tspan
                  sodipodi:role="line"
                  id="tspan53"
                  x="299.33215"
                  y="149.42789">Plaridel</tspan>
              </text>
              <text
                xml:space="preserve"
                style="font-style:italic;font-variant:normal;font-weight:bold;font-stretch:normal;font-size:3.33333px;font-family:sans-serif;-inkscape-font-specification:'sans-serif Bold Italic';text-align:start;writing-mode:lr-tb;direction:ltr;text-anchor:start;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                x="268.20795"
                y="182.93402"
                id="text54">
                <tspan
                  sodipodi:role="line"
                  id="tspan54"
                  x="268.20795"
                  y="182.93402">General Luna</tspan>
              </text>
              <text
                xml:space="preserve"
                style="font-style:italic;font-variant:normal;font-weight:bold;font-stretch:normal;font-size:3.33333px;font-family:sans-serif;-inkscape-font-specification:'sans-serif Bold Italic';text-align:start;writing-mode:lr-tb;direction:ltr;text-anchor:start;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                x="194.8438"
                y="225.49159"
                id="text55">
                <tspan
                  sodipodi:role="line"
                  id="tspan55"
                  x="194.8438"
                  y="225.49159">Campo </tspan>
                <tspan
                  sodipodi:role="line"
                  x="194.8438"
                  y="229.65825"
                  id="tspan56">Himuga-an</tspan>
              </text>
              <text
                xml:space="preserve"
                style="font-style:italic;font-variant:normal;font-weight:bold;font-stretch:normal;font-size:3.33333px;font-family:sans-serif;-inkscape-font-specification:'sans-serif Bold Italic';text-align:start;writing-mode:lr-tb;direction:ltr;text-anchor:start;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                x="206.95282"
                y="253.59863"
                id="text57">
                <tspan
                  sodipodi:role="line"
                  id="tspan57"
                  x="206.95282"
                  y="253.59863">Bato</tspan>
              </text>
              <text
                xml:space="preserve"
                style="font-style:italic;font-variant:normal;font-weight:bold;font-stretch:normal;font-size:3.33333px;font-family:sans-serif;-inkscape-font-specification:'sans-serif Bold Italic';text-align:start;writing-mode:lr-tb;direction:ltr;text-anchor:start;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                x="246.33913"
                y="235.82741"
                id="text58">
                <tspan
                  sodipodi:role="line"
                  id="tspan58"
                  x="246.33913"
                  y="235.82741">Lopez Jaena</tspan>
              </text>
              <text
                xml:space="preserve"
                style="font-style:italic;font-variant:normal;font-weight:bold;font-stretch:normal;font-size:3.33333px;font-family:sans-serif;-inkscape-font-specification:'sans-serif Bold Italic';text-align:start;writing-mode:lr-tb;direction:ltr;text-anchor:start;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                x="237.08376"
                y="208.81792"
                id="text59">
                <tspan
                  sodipodi:role="line"
                  id="tspan59"
                  x="237.08376"
                  y="208.81792">Rizal</tspan>
              </text>
              <text
                xml:space="preserve"
                style="font-style:italic;font-variant:normal;font-weight:bold;font-stretch:normal;font-size:3.33333px;font-family:sans-serif;-inkscape-font-specification:'sans-serif Bold Italic';text-align:start;writing-mode:lr-tb;direction:ltr;text-anchor:start;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                x="227.07956"
                y="150.85706"
                id="text60">
                <tspan
                  sodipodi:role="line"
                  id="tspan60"
                  x="227.07956"
                  y="150.85706">Poblacion II</tspan>
              </text>
              <text
                xml:space="preserve"
                style="font-style:italic;font-variant:normal;font-weight:bold;font-stretch:normal;font-size:3.33333px;font-family:sans-serif;-inkscape-font-specification:'sans-serif Bold Italic';text-align:start;writing-mode:lr-tb;direction:ltr;text-anchor:start;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                x="173.22525"
                y="-250.66884"
                id="text61"
                transform="rotate(90)">
                <tspan
                  sodipodi:role="line"
                  id="tspan61"
                  x="173.22525"
                  y="-250.66884">Poblacion I</tspan>
              </text>
              <text
                xml:space="preserve"
                style="font-style:italic;font-variant:normal;font-weight:bold;font-stretch:normal;font-size:3.33333px;font-family:sans-serif;-inkscape-font-specification:'sans-serif Bold Italic';text-align:start;writing-mode:lr-tb;direction:ltr;text-anchor:start;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1.12252;stroke-linecap:square;stroke-linejoin:round;paint-order:markers stroke fill"
                x="440.67166"
                y="60.952229"
                id="text62">
                <tspan
                  sodipodi:role="line"
                  id="tspan62"
                  x="440.67166"
                  y="60.952229">Molocaboc</tspan>
              </text>
              <g
                id="g4-7"
                style="overflow:hidden;fill:currentColor"
                transform="matrix(0.01579114,0,0,0.01232821,248.79224,62.027167)">
                <path
                  d="m 537.42933,24.746667 c -9.38666,-0.512 -18.09066,-0.853333 -25.77066,-0.853334 -205.99467,0 -380.416,153.770667 -405.67467,357.546667 -12.458667,100.01067 12.62933,200.704 70.656,283.648 57.856,82.60267 143.53067,140.8 241.32267,163.84 L 428.032,831.31733 512,976.384 595.968,831.31733 606.03733,828.928 c 89.088,-20.992 169.472,-71.85067 226.47467,-143.53067 57.17333,-71.85066 88.74667,-161.792 88.74667,-253.44 0,-215.04 -168.61867,-393.898664 -383.82934,-407.210663 z"
                  fill="#070f1b"
                  id="path1-1" />
                <path
                  d="M 538.79467,0.853333 C 529.92,0.341333 520.704,0 511.65867,0 293.71733,0 109.056,162.64533 82.261333,378.53867 c -27.306667,219.30666 116.565337,423.424 330.239997,473.6 L 475.136,960.34133 512,1024 548.864,960.34133 611.49867,852.13867 C 805.888,806.4 945.152,632.832 945.152,431.95733 945.152,204.288 766.80533,15.018667 538.79467,0.853333 Z M 606.03733,828.928 595.968,831.31733 512,976.384 428.032,831.31733 417.96267,828.928 C 320.17067,805.888 234.496,747.86133 176.64,665.088 118.61333,582.144 93.525333,481.45067 105.984,381.44 131.24267,177.664 305.664,23.893333 511.65867,23.893333 c 7.68,0 16.384,0.341333 25.77066,0.853334 215.21067,13.312 383.82934,192.341333 383.82934,407.210663 0,91.47734 -31.57334,181.58934 -88.74667,253.44 C 775.50933,756.90667 695.12533,807.936 606.03733,828.928 Z"
                  fill="#9b7bff"
                  id="path2-1" />
                <path
                  d="m 767.488,313.17333 c -12.97067,-6.48533 -217.94133,-99.328 -217.94133,-99.328 0,0 -21.504,-10.92266 -32.42667,-10.92266 -10.92267,0 -32.42667,10.92266 -32.42667,10.92266 0,0 -205.14133,92.84267 -218.112,99.328 -12.97066,6.48534 0,12.97067 0,12.97067 0,0 174.93334,79.70133 207.18934,92.84267 25.94133,10.92266 32.42666,10.92266 43.17866,10.92266 10.92267,0 17.23734,0 43.17867,-10.92266 21.504,-10.92267 112.128,-51.712 166.22933,-75.60534 v 69.12 l -17.23733,17.23734 23.72267,23.72266 23.72266,-23.72266 -17.06666,-17.23734 V 339.11467 C 756.736,332.62933 767.488,326.144 767.488,326.144 c 0,0 12.8,-6.48533 0,-12.97067 z"
                  fill="#9b7bff"
                  id="path3-8-5" />
                <path
                  d="m 517.12,474.624 c -10.92267,0 -17.23733,0 -43.17867,-10.92267 C 458.752,457.216 407.04,433.49333 359.59467,411.98933 v 157.52534 c 47.616,21.504 99.328,45.39733 114.34666,51.712 25.94134,10.92266 32.42667,10.92266 43.17867,10.92266 10.92267,0 17.23733,0 43.17867,-10.92266 15.18933,-6.48534 66.90133,-30.208 114.34666,-51.712 V 411.98933 c -47.616,21.504 -99.328,45.39734 -114.34666,51.712 C 534.35733,476.84267 527.872,474.624 517.12,474.624 Z"
                  fill="#9b7bff"
                  id="path4-2-2" />
              </g>
              <g
                id="g4-2"
                style="overflow:hidden;fill:currentColor"
                transform="matrix(0.01579114,0,0,0.01232821,255.42743,83.219276)">
                <path
                  d="m 537.42933,24.746667 c -9.38666,-0.512 -18.09066,-0.853333 -25.77066,-0.853334 -205.99467,0 -380.416,153.770667 -405.67467,357.546667 -12.458667,100.01067 12.62933,200.704 70.656,283.648 57.856,82.60267 143.53067,140.8 241.32267,163.84 L 428.032,831.31733 512,976.384 595.968,831.31733 606.03733,828.928 c 89.088,-20.992 169.472,-71.85067 226.47467,-143.53067 57.17333,-71.85066 88.74667,-161.792 88.74667,-253.44 0,-215.04 -168.61867,-393.898664 -383.82934,-407.210663 z"
                  fill="#070f1b"
                  id="path1-3" />
                <path
                  d="M 538.79467,0.853333 C 529.92,0.341333 520.704,0 511.65867,0 293.71733,0 109.056,162.64533 82.261333,378.53867 c -27.306667,219.30666 116.565337,423.424 330.239997,473.6 L 475.136,960.34133 512,1024 548.864,960.34133 611.49867,852.13867 C 805.888,806.4 945.152,632.832 945.152,431.95733 945.152,204.288 766.80533,15.018667 538.79467,0.853333 Z M 606.03733,828.928 595.968,831.31733 512,976.384 428.032,831.31733 417.96267,828.928 C 320.17067,805.888 234.496,747.86133 176.64,665.088 118.61333,582.144 93.525333,481.45067 105.984,381.44 131.24267,177.664 305.664,23.893333 511.65867,23.893333 c 7.68,0 16.384,0.341333 25.77066,0.853334 215.21067,13.312 383.82934,192.341333 383.82934,407.210663 0,91.47734 -31.57334,181.58934 -88.74667,253.44 C 775.50933,756.90667 695.12533,807.936 606.03733,828.928 Z"
                  fill="#9b7bff"
                  id="path2-2" />
                <path
                  d="m 767.488,313.17333 c -12.97067,-6.48533 -217.94133,-99.328 -217.94133,-99.328 0,0 -21.504,-10.92266 -32.42667,-10.92266 -10.92267,0 -32.42667,10.92266 -32.42667,10.92266 0,0 -205.14133,92.84267 -218.112,99.328 -12.97066,6.48534 0,12.97067 0,12.97067 0,0 174.93334,79.70133 207.18934,92.84267 25.94133,10.92266 32.42666,10.92266 43.17866,10.92266 10.92267,0 17.23734,0 43.17867,-10.92266 21.504,-10.92267 112.128,-51.712 166.22933,-75.60534 v 69.12 l -17.23733,17.23734 23.72267,23.72266 23.72266,-23.72266 -17.06666,-17.23734 V 339.11467 C 756.736,332.62933 767.488,326.144 767.488,326.144 c 0,0 12.8,-6.48533 0,-12.97067 z"
                  fill="#9b7bff"
                  id="path3-8-2" />
                <path
                  d="m 517.12,474.624 c -10.92267,0 -17.23733,0 -43.17867,-10.92267 C 458.752,457.216 407.04,433.49333 359.59467,411.98933 v 157.52534 c 47.616,21.504 99.328,45.39733 114.34666,51.712 25.94134,10.92266 32.42667,10.92266 43.17867,10.92266 10.92267,0 17.23733,0 43.17867,-10.92266 15.18933,-6.48534 66.90133,-30.208 114.34666,-51.712 V 411.98933 c -47.616,21.504 -99.328,45.39734 -114.34666,51.712 C 534.35733,476.84267 527.872,474.624 517.12,474.624 Z"
                  fill="#9b7bff"
                  id="path4-2-1" />
              </g>
              <g
                id="g4-6"
                style="overflow:hidden;fill:currentColor"
                transform="matrix(0.01579114,0,0,0.01232821,298.71979,110.37451)">
                <path
                  d="m 537.42933,24.746667 c -9.38666,-0.512 -18.09066,-0.853333 -25.77066,-0.853334 -205.99467,0 -380.416,153.770667 -405.67467,357.546667 -12.458667,100.01067 12.62933,200.704 70.656,283.648 57.856,82.60267 143.53067,140.8 241.32267,163.84 L 428.032,831.31733 512,976.384 595.968,831.31733 606.03733,828.928 c 89.088,-20.992 169.472,-71.85067 226.47467,-143.53067 57.17333,-71.85066 88.74667,-161.792 88.74667,-253.44 0,-215.04 -168.61867,-393.898664 -383.82934,-407.210663 z"
                  fill="#070f1b"
                  id="path1-18" />
                <path
                  d="M 538.79467,0.853333 C 529.92,0.341333 520.704,0 511.65867,0 293.71733,0 109.056,162.64533 82.261333,378.53867 c -27.306667,219.30666 116.565337,423.424 330.239997,473.6 L 475.136,960.34133 512,1024 548.864,960.34133 611.49867,852.13867 C 805.888,806.4 945.152,632.832 945.152,431.95733 945.152,204.288 766.80533,15.018667 538.79467,0.853333 Z M 606.03733,828.928 595.968,831.31733 512,976.384 428.032,831.31733 417.96267,828.928 C 320.17067,805.888 234.496,747.86133 176.64,665.088 118.61333,582.144 93.525333,481.45067 105.984,381.44 131.24267,177.664 305.664,23.893333 511.65867,23.893333 c 7.68,0 16.384,0.341333 25.77066,0.853334 215.21067,13.312 383.82934,192.341333 383.82934,407.210663 0,91.47734 -31.57334,181.58934 -88.74667,253.44 C 775.50933,756.90667 695.12533,807.936 606.03733,828.928 Z"
                  fill="#9b7bff"
                  id="path2-9" />
                <path
                  d="m 767.488,313.17333 c -12.97067,-6.48533 -217.94133,-99.328 -217.94133,-99.328 0,0 -21.504,-10.92266 -32.42667,-10.92266 -10.92267,0 -32.42667,10.92266 -32.42667,10.92266 0,0 -205.14133,92.84267 -218.112,99.328 -12.97066,6.48534 0,12.97067 0,12.97067 0,0 174.93334,79.70133 207.18934,92.84267 25.94133,10.92266 32.42666,10.92266 43.17866,10.92266 10.92267,0 17.23734,0 43.17867,-10.92266 21.504,-10.92267 112.128,-51.712 166.22933,-75.60534 v 69.12 l -17.23733,17.23734 23.72267,23.72266 23.72266,-23.72266 -17.06666,-17.23734 V 339.11467 C 756.736,332.62933 767.488,326.144 767.488,326.144 c 0,0 12.8,-6.48533 0,-12.97067 z"
                  fill="#9b7bff"
                  id="path3-8-27" />
                <path
                  d="m 517.12,474.624 c -10.92267,0 -17.23733,0 -43.17867,-10.92267 C 458.752,457.216 407.04,433.49333 359.59467,411.98933 v 157.52534 c 47.616,21.504 99.328,45.39733 114.34666,51.712 25.94134,10.92266 32.42667,10.92266 43.17867,10.92266 10.92267,0 17.23733,0 43.17867,-10.92266 15.18933,-6.48534 66.90133,-30.208 114.34666,-51.712 V 411.98933 c -47.616,21.504 -99.328,45.39734 -114.34666,51.712 C 534.35733,476.84267 527.872,474.624 517.12,474.624 Z"
                  fill="#9b7bff"
                  id="path4-2-9" />
              </g>
              <g
                id="g4-23"
                style="overflow:hidden;fill:currentColor"
                transform="matrix(0.01579114,0,0,0.01232821,216.43196,130.69146)">
                <path
                  d="m 537.42933,24.746667 c -9.38666,-0.512 -18.09066,-0.853333 -25.77066,-0.853334 -205.99467,0 -380.416,153.770667 -405.67467,357.546667 -12.458667,100.01067 12.62933,200.704 70.656,283.648 57.856,82.60267 143.53067,140.8 241.32267,163.84 L 428.032,831.31733 512,976.384 595.968,831.31733 606.03733,828.928 c 89.088,-20.992 169.472,-71.85067 226.47467,-143.53067 57.17333,-71.85066 88.74667,-161.792 88.74667,-253.44 0,-215.04 -168.61867,-393.898664 -383.82934,-407.210663 z"
                  fill="#070f1b"
                  id="path1-34" />
                <path
                  d="M 538.79467,0.853333 C 529.92,0.341333 520.704,0 511.65867,0 293.71733,0 109.056,162.64533 82.261333,378.53867 c -27.306667,219.30666 116.565337,423.424 330.239997,473.6 L 475.136,960.34133 512,1024 548.864,960.34133 611.49867,852.13867 C 805.888,806.4 945.152,632.832 945.152,431.95733 945.152,204.288 766.80533,15.018667 538.79467,0.853333 Z M 606.03733,828.928 595.968,831.31733 512,976.384 428.032,831.31733 417.96267,828.928 C 320.17067,805.888 234.496,747.86133 176.64,665.088 118.61333,582.144 93.525333,481.45067 105.984,381.44 131.24267,177.664 305.664,23.893333 511.65867,23.893333 c 7.68,0 16.384,0.341333 25.77066,0.853334 215.21067,13.312 383.82934,192.341333 383.82934,407.210663 0,91.47734 -31.57334,181.58934 -88.74667,253.44 C 775.50933,756.90667 695.12533,807.936 606.03733,828.928 Z"
                  fill="#9b7bff"
                  id="path2-11" />
                <path
                  d="m 767.488,313.17333 c -12.97067,-6.48533 -217.94133,-99.328 -217.94133,-99.328 0,0 -21.504,-10.92266 -32.42667,-10.92266 -10.92267,0 -32.42667,10.92266 -32.42667,10.92266 0,0 -205.14133,92.84267 -218.112,99.328 -12.97066,6.48534 0,12.97067 0,12.97067 0,0 174.93334,79.70133 207.18934,92.84267 25.94133,10.92266 32.42666,10.92266 43.17866,10.92266 10.92267,0 17.23734,0 43.17867,-10.92266 21.504,-10.92267 112.128,-51.712 166.22933,-75.60534 v 69.12 l -17.23733,17.23734 23.72267,23.72266 23.72266,-23.72266 -17.06666,-17.23734 V 339.11467 C 756.736,332.62933 767.488,326.144 767.488,326.144 c 0,0 12.8,-6.48533 0,-12.97067 z"
                  fill="#9b7bff"
                  id="path3-8-3" />
                <path
                  d="m 517.12,474.624 c -10.92267,0 -17.23733,0 -43.17867,-10.92267 C 458.752,457.216 407.04,433.49333 359.59467,411.98933 v 157.52534 c 47.616,21.504 99.328,45.39733 114.34666,51.712 25.94134,10.92266 32.42667,10.92266 43.17867,10.92266 10.92267,0 17.23733,0 43.17867,-10.92266 15.18933,-6.48534 66.90133,-30.208 114.34666,-51.712 V 411.98933 c -47.616,21.504 -99.328,45.39734 -114.34666,51.712 C 534.35733,476.84267 527.872,474.624 517.12,474.624 Z"
                  fill="#9b7bff"
                  id="path4-2-8" />
              </g>
              <g
                id="g4-79"
                style="overflow:hidden;fill:currentColor"
                transform="matrix(0.01579114,0,0,0.01232821,195.13118,137.94437)">
                <path
                  d="m 537.42933,24.746667 c -9.38666,-0.512 -18.09066,-0.853333 -25.77066,-0.853334 -205.99467,0 -380.416,153.770667 -405.67467,357.546667 -12.458667,100.01067 12.62933,200.704 70.656,283.648 57.856,82.60267 143.53067,140.8 241.32267,163.84 L 428.032,831.31733 512,976.384 595.968,831.31733 606.03733,828.928 c 89.088,-20.992 169.472,-71.85067 226.47467,-143.53067 57.17333,-71.85066 88.74667,-161.792 88.74667,-253.44 0,-215.04 -168.61867,-393.898664 -383.82934,-407.210663 z"
                  fill="#070f1b"
                  id="path1-31" />
                <path
                  d="M 538.79467,0.853333 C 529.92,0.341333 520.704,0 511.65867,0 293.71733,0 109.056,162.64533 82.261333,378.53867 c -27.306667,219.30666 116.565337,423.424 330.239997,473.6 L 475.136,960.34133 512,1024 548.864,960.34133 611.49867,852.13867 C 805.888,806.4 945.152,632.832 945.152,431.95733 945.152,204.288 766.80533,15.018667 538.79467,0.853333 Z M 606.03733,828.928 595.968,831.31733 512,976.384 428.032,831.31733 417.96267,828.928 C 320.17067,805.888 234.496,747.86133 176.64,665.088 118.61333,582.144 93.525333,481.45067 105.984,381.44 131.24267,177.664 305.664,23.893333 511.65867,23.893333 c 7.68,0 16.384,0.341333 25.77066,0.853334 215.21067,13.312 383.82934,192.341333 383.82934,407.210663 0,91.47734 -31.57334,181.58934 -88.74667,253.44 C 775.50933,756.90667 695.12533,807.936 606.03733,828.928 Z"
                  fill="#9b7bff"
                  id="path2-98" />
                <path
                  d="m 767.488,313.17333 c -12.97067,-6.48533 -217.94133,-99.328 -217.94133,-99.328 0,0 -21.504,-10.92266 -32.42667,-10.92266 -10.92267,0 -32.42667,10.92266 -32.42667,10.92266 0,0 -205.14133,92.84267 -218.112,99.328 -12.97066,6.48534 0,12.97067 0,12.97067 0,0 174.93334,79.70133 207.18934,92.84267 25.94133,10.92266 32.42666,10.92266 43.17866,10.92266 10.92267,0 17.23734,0 43.17867,-10.92266 21.504,-10.92267 112.128,-51.712 166.22933,-75.60534 v 69.12 l -17.23733,17.23734 23.72267,23.72266 23.72266,-23.72266 -17.06666,-17.23734 V 339.11467 C 756.736,332.62933 767.488,326.144 767.488,326.144 c 0,0 12.8,-6.48533 0,-12.97067 z"
                  fill="#9b7bff"
                  id="path3-8-6" />
                <path
                  d="m 517.12,474.624 c -10.92267,0 -17.23733,0 -43.17867,-10.92267 C 458.752,457.216 407.04,433.49333 359.59467,411.98933 v 157.52534 c 47.616,21.504 99.328,45.39733 114.34666,51.712 25.94134,10.92266 32.42667,10.92266 43.17867,10.92266 10.92267,0 17.23733,0 43.17867,-10.92266 15.18933,-6.48534 66.90133,-30.208 114.34666,-51.712 V 411.98933 c -47.616,21.504 -99.328,45.39734 -114.34666,51.712 C 534.35733,476.84267 527.872,474.624 517.12,474.624 Z"
                  fill="#9b7bff"
                  id="path4-2-5" />
              </g>
              <g
                id="g4-0"
                style="overflow:hidden;fill:currentColor"
                transform="matrix(0.01579114,0,0,0.01232821,188.73726,245.33894)">
                <path
                  d="m 537.42933,24.746667 c -9.38666,-0.512 -18.09066,-0.853333 -25.77066,-0.853334 -205.99467,0 -380.416,153.770667 -405.67467,357.546667 -12.458667,100.01067 12.62933,200.704 70.656,283.648 57.856,82.60267 143.53067,140.8 241.32267,163.84 L 428.032,831.31733 512,976.384 595.968,831.31733 606.03733,828.928 c 89.088,-20.992 169.472,-71.85067 226.47467,-143.53067 57.17333,-71.85066 88.74667,-161.792 88.74667,-253.44 0,-215.04 -168.61867,-393.898664 -383.82934,-407.210663 z"
                  fill="#070f1b"
                  id="path1-2" />
                <path
                  d="M 538.79467,0.853333 C 529.92,0.341333 520.704,0 511.65867,0 293.71733,0 109.056,162.64533 82.261333,378.53867 c -27.306667,219.30666 116.565337,423.424 330.239997,473.6 L 475.136,960.34133 512,1024 548.864,960.34133 611.49867,852.13867 C 805.888,806.4 945.152,632.832 945.152,431.95733 945.152,204.288 766.80533,15.018667 538.79467,0.853333 Z M 606.03733,828.928 595.968,831.31733 512,976.384 428.032,831.31733 417.96267,828.928 C 320.17067,805.888 234.496,747.86133 176.64,665.088 118.61333,582.144 93.525333,481.45067 105.984,381.44 131.24267,177.664 305.664,23.893333 511.65867,23.893333 c 7.68,0 16.384,0.341333 25.77066,0.853334 215.21067,13.312 383.82934,192.341333 383.82934,407.210663 0,91.47734 -31.57334,181.58934 -88.74667,253.44 C 775.50933,756.90667 695.12533,807.936 606.03733,828.928 Z"
                  fill="#9b7bff"
                  id="path2-4" />
                <path
                  d="m 767.488,313.17333 c -12.97067,-6.48533 -217.94133,-99.328 -217.94133,-99.328 0,0 -21.504,-10.92266 -32.42667,-10.92266 -10.92267,0 -32.42667,10.92266 -32.42667,10.92266 0,0 -205.14133,92.84267 -218.112,99.328 -12.97066,6.48534 0,12.97067 0,12.97067 0,0 174.93334,79.70133 207.18934,92.84267 25.94133,10.92266 32.42666,10.92266 43.17866,10.92266 10.92267,0 17.23734,0 43.17867,-10.92266 21.504,-10.92267 112.128,-51.712 166.22933,-75.60534 v 69.12 l -17.23733,17.23734 23.72267,23.72266 23.72266,-23.72266 -17.06666,-17.23734 V 339.11467 C 756.736,332.62933 767.488,326.144 767.488,326.144 c 0,0 12.8,-6.48533 0,-12.97067 z"
                  fill="#9b7bff"
                  id="path3-8-8" />
                <path
                  d="m 517.12,474.624 c -10.92267,0 -17.23733,0 -43.17867,-10.92267 C 458.752,457.216 407.04,433.49333 359.59467,411.98933 v 157.52534 c 47.616,21.504 99.328,45.39733 114.34666,51.712 25.94134,10.92266 32.42667,10.92266 43.17867,10.92266 10.92267,0 17.23733,0 43.17867,-10.92266 15.18933,-6.48534 66.90133,-30.208 114.34666,-51.712 V 411.98933 c -47.616,21.504 -99.328,45.39734 -114.34666,51.712 C 534.35733,476.84267 527.872,474.624 517.12,474.624 Z"
                  fill="#9b7bff"
                  id="path4-2-6" />
              </g>
              <g
                id="g4-06"
                style="overflow:hidden;fill:currentColor"
                transform="matrix(0.01579114,0,0,0.01232821,213.47465,232.76843)">
                <path
                  d="m 537.42933,24.746667 c -9.38666,-0.512 -18.09066,-0.853333 -25.77066,-0.853334 -205.99467,0 -380.416,153.770667 -405.67467,357.546667 -12.458667,100.01067 12.62933,200.704 70.656,283.648 57.856,82.60267 143.53067,140.8 241.32267,163.84 L 428.032,831.31733 512,976.384 595.968,831.31733 606.03733,828.928 c 89.088,-20.992 169.472,-71.85067 226.47467,-143.53067 57.17333,-71.85066 88.74667,-161.792 88.74667,-253.44 0,-215.04 -168.61867,-393.898664 -383.82934,-407.210663 z"
                  fill="#070f1b"
                  id="path1-13" />
                <path
                  d="M 538.79467,0.853333 C 529.92,0.341333 520.704,0 511.65867,0 293.71733,0 109.056,162.64533 82.261333,378.53867 c -27.306667,219.30666 116.565337,423.424 330.239997,473.6 L 475.136,960.34133 512,1024 548.864,960.34133 611.49867,852.13867 C 805.888,806.4 945.152,632.832 945.152,431.95733 945.152,204.288 766.80533,15.018667 538.79467,0.853333 Z M 606.03733,828.928 595.968,831.31733 512,976.384 428.032,831.31733 417.96267,828.928 C 320.17067,805.888 234.496,747.86133 176.64,665.088 118.61333,582.144 93.525333,481.45067 105.984,381.44 131.24267,177.664 305.664,23.893333 511.65867,23.893333 c 7.68,0 16.384,0.341333 25.77066,0.853334 215.21067,13.312 383.82934,192.341333 383.82934,407.210663 0,91.47734 -31.57334,181.58934 -88.74667,253.44 C 775.50933,756.90667 695.12533,807.936 606.03733,828.928 Z"
                  fill="#9b7bff"
                  id="path2-8" />
                <path
                  d="m 767.488,313.17333 c -12.97067,-6.48533 -217.94133,-99.328 -217.94133,-99.328 0,0 -21.504,-10.92266 -32.42667,-10.92266 -10.92267,0 -32.42667,10.92266 -32.42667,10.92266 0,0 -205.14133,92.84267 -218.112,99.328 -12.97066,6.48534 0,12.97067 0,12.97067 0,0 174.93334,79.70133 207.18934,92.84267 25.94133,10.92266 32.42666,10.92266 43.17866,10.92266 10.92267,0 17.23734,0 43.17867,-10.92266 21.504,-10.92267 112.128,-51.712 166.22933,-75.60534 v 69.12 l -17.23733,17.23734 23.72267,23.72266 23.72266,-23.72266 -17.06666,-17.23734 V 339.11467 C 756.736,332.62933 767.488,326.144 767.488,326.144 c 0,0 12.8,-6.48533 0,-12.97067 z"
                  fill="#9b7bff"
                  id="path3-8-9" />
                <path
                  d="m 517.12,474.624 c -10.92267,0 -17.23733,0 -43.17867,-10.92267 C 458.752,457.216 407.04,433.49333 359.59467,411.98933 v 157.52534 c 47.616,21.504 99.328,45.39733 114.34666,51.712 25.94134,10.92266 32.42667,10.92266 43.17867,10.92266 10.92267,0 17.23733,0 43.17867,-10.92266 15.18933,-6.48534 66.90133,-30.208 114.34666,-51.712 V 411.98933 c -47.616,21.504 -99.328,45.39734 -114.34666,51.712 C 534.35733,476.84267 527.872,474.624 517.12,474.624 Z"
                  fill="#9b7bff"
                  id="path4-2-3" />
              </g>
              <g
                id="g4-66"
                style="overflow:hidden;fill:currentColor"
                transform="matrix(0.01579114,0,0,0.01232821,250.10595,154.63162)">
                <path
                  d="m 537.42933,24.746667 c -9.38666,-0.512 -18.09066,-0.853333 -25.77066,-0.853334 -205.99467,0 -380.416,153.770667 -405.67467,357.546667 -12.458667,100.01067 12.62933,200.704 70.656,283.648 57.856,82.60267 143.53067,140.8 241.32267,163.84 L 428.032,831.31733 512,976.384 595.968,831.31733 606.03733,828.928 c 89.088,-20.992 169.472,-71.85067 226.47467,-143.53067 57.17333,-71.85066 88.74667,-161.792 88.74667,-253.44 0,-215.04 -168.61867,-393.898664 -383.82934,-407.210663 z"
                  fill="#070f1b"
                  id="path1-184" />
                <path
                  d="M 538.79467,0.853333 C 529.92,0.341333 520.704,0 511.65867,0 293.71733,0 109.056,162.64533 82.261333,378.53867 c -27.306667,219.30666 116.565337,423.424 330.239997,473.6 L 475.136,960.34133 512,1024 548.864,960.34133 611.49867,852.13867 C 805.888,806.4 945.152,632.832 945.152,431.95733 945.152,204.288 766.80533,15.018667 538.79467,0.853333 Z M 606.03733,828.928 595.968,831.31733 512,976.384 428.032,831.31733 417.96267,828.928 C 320.17067,805.888 234.496,747.86133 176.64,665.088 118.61333,582.144 93.525333,481.45067 105.984,381.44 131.24267,177.664 305.664,23.893333 511.65867,23.893333 c 7.68,0 16.384,0.341333 25.77066,0.853334 215.21067,13.312 383.82934,192.341333 383.82934,407.210663 0,91.47734 -31.57334,181.58934 -88.74667,253.44 C 775.50933,756.90667 695.12533,807.936 606.03733,828.928 Z"
                  fill="#9b7bff"
                  id="path2-96" />
                <path
                  d="m 767.488,313.17333 c -12.97067,-6.48533 -217.94133,-99.328 -217.94133,-99.328 0,0 -21.504,-10.92266 -32.42667,-10.92266 -10.92267,0 -32.42667,10.92266 -32.42667,10.92266 0,0 -205.14133,92.84267 -218.112,99.328 -12.97066,6.48534 0,12.97067 0,12.97067 0,0 174.93334,79.70133 207.18934,92.84267 25.94133,10.92266 32.42666,10.92266 43.17866,10.92266 10.92267,0 17.23734,0 43.17867,-10.92266 21.504,-10.92267 112.128,-51.712 166.22933,-75.60534 v 69.12 l -17.23733,17.23734 23.72267,23.72266 23.72266,-23.72266 -17.06666,-17.23734 V 339.11467 C 756.736,332.62933 767.488,326.144 767.488,326.144 c 0,0 12.8,-6.48533 0,-12.97067 z"
                  fill="#9b7bff"
                  id="path3-8-37" />
                <path
                  d="m 517.12,474.624 c -10.92267,0 -17.23733,0 -43.17867,-10.92267 C 458.752,457.216 407.04,433.49333 359.59467,411.98933 v 157.52534 c 47.616,21.504 99.328,45.39733 114.34666,51.712 25.94134,10.92266 32.42667,10.92266 43.17867,10.92266 10.92267,0 17.23733,0 43.17867,-10.92266 15.18933,-6.48534 66.90133,-30.208 114.34666,-51.712 V 411.98933 c -47.616,21.504 -99.328,45.39734 -114.34666,51.712 C 534.35733,476.84267 527.872,474.624 517.12,474.624 Z"
                  fill="#9b7bff"
                  id="path4-2-88" />
              </g>
              <g
                id="g4-5"
                style="overflow:hidden;fill:currentColor"
                transform="matrix(0.01579114,0,0,0.01232821,244.4229,127.34347)"
                data-school-id="1">
                <path
                  d="m 537.42933,24.746667 c -9.38666,-0.512 -18.09066,-0.853333 -25.77066,-0.853334 -205.99467,0 -380.416,153.770667 -405.67467,357.546667 -12.458667,100.01067 12.62933,200.704 70.656,283.648 57.856,82.60267 143.53067,140.8 241.32267,163.84 L 428.032,831.31733 512,976.384 595.968,831.31733 606.03733,828.928 c 89.088,-20.992 169.472,-71.85067 226.47467,-143.53067 57.17333,-71.85066 88.74667,-161.792 88.74667,-253.44 0,-215.04 -168.61867,-393.898664 -383.82934,-407.210663 z"
                  fill="#070f1b"
                  id="path1-9" />
                <path
                  d="M 538.79467,0.853333 C 529.92,0.341333 520.704,0 511.65867,0 293.71733,0 109.056,162.64533 82.261333,378.53867 c -27.306667,219.30666 116.565337,423.424 330.239997,473.6 L 475.136,960.34133 512,1024 548.864,960.34133 611.49867,852.13867 C 805.888,806.4 945.152,632.832 945.152,431.95733 945.152,204.288 766.80533,15.018667 538.79467,0.853333 Z M 606.03733,828.928 595.968,831.31733 512,976.384 428.032,831.31733 417.96267,828.928 C 320.17067,805.888 234.496,747.86133 176.64,665.088 118.61333,582.144 93.525333,481.45067 105.984,381.44 131.24267,177.664 305.664,23.893333 511.65867,23.893333 c 7.68,0 16.384,0.341333 25.77066,0.853334 215.21067,13.312 383.82934,192.341333 383.82934,407.210663 0,91.47734 -31.57334,181.58934 -88.74667,253.44 C 775.50933,756.90667 695.12533,807.936 606.03733,828.928 Z"
                  fill="#9b7bff"
                  id="path2-84" />
                <path
                  d="m 767.488,313.17333 c -12.97067,-6.48533 -217.94133,-99.328 -217.94133,-99.328 0,0 -21.504,-10.92266 -32.42667,-10.92266 -10.92267,0 -32.42667,10.92266 -32.42667,10.92266 0,0 -205.14133,92.84267 -218.112,99.328 -12.97066,6.48534 0,12.97067 0,12.97067 0,0 174.93334,79.70133 207.18934,92.84267 25.94133,10.92266 32.42666,10.92266 43.17866,10.92266 10.92267,0 17.23734,0 43.17867,-10.92266 21.504,-10.92267 112.128,-51.712 166.22933,-75.60534 v 69.12 l -17.23733,17.23734 23.72267,23.72266 23.72266,-23.72266 -17.06666,-17.23734 V 339.11467 C 756.736,332.62933 767.488,326.144 767.488,326.144 c 0,0 12.8,-6.48533 0,-12.97067 z"
                  fill="#9b7bff"
                  id="path3-8-0" />
                <path
                  d="m 517.12,474.624 c -10.92267,0 -17.23733,0 -43.17867,-10.92267 C 458.752,457.216 407.04,433.49333 359.59467,411.98933 v 157.52534 c 47.616,21.504 99.328,45.39733 114.34666,51.712 25.94134,10.92266 32.42667,10.92266 43.17867,10.92266 10.92267,0 17.23733,0 43.17867,-10.92266 15.18933,-6.48534 66.90133,-30.208 114.34666,-51.712 V 411.98933 c -47.616,21.504 -99.328,45.39734 -114.34666,51.712 C 534.35733,476.84267 527.872,474.624 517.12,474.624 Z"
                  fill="#9b7bff"
                  id="path4-2-7" />
              </g>
              <g
                id="g4-77"
                style="overflow:hidden;fill:currentColor"
                transform="matrix(0.01579114,0,0,0.01232821,254.9381,145.68273)">
                <path
                  d="m 537.42933,24.746667 c -9.38666,-0.512 -18.09066,-0.853333 -25.77066,-0.853334 -205.99467,0 -380.416,153.770667 -405.67467,357.546667 -12.458667,100.01067 12.62933,200.704 70.656,283.648 57.856,82.60267 143.53067,140.8 241.32267,163.84 L 428.032,831.31733 512,976.384 595.968,831.31733 606.03733,828.928 c 89.088,-20.992 169.472,-71.85067 226.47467,-143.53067 57.17333,-71.85066 88.74667,-161.792 88.74667,-253.44 0,-215.04 -168.61867,-393.898664 -383.82934,-407.210663 z"
                  fill="#070f1b"
                  id="path1-7" />
                <path
                  d="M 538.79467,0.853333 C 529.92,0.341333 520.704,0 511.65867,0 293.71733,0 109.056,162.64533 82.261333,378.53867 c -27.306667,219.30666 116.565337,423.424 330.239997,473.6 L 475.136,960.34133 512,1024 548.864,960.34133 611.49867,852.13867 C 805.888,806.4 945.152,632.832 945.152,431.95733 945.152,204.288 766.80533,15.018667 538.79467,0.853333 Z M 606.03733,828.928 595.968,831.31733 512,976.384 428.032,831.31733 417.96267,828.928 C 320.17067,805.888 234.496,747.86133 176.64,665.088 118.61333,582.144 93.525333,481.45067 105.984,381.44 131.24267,177.664 305.664,23.893333 511.65867,23.893333 c 7.68,0 16.384,0.341333 25.77066,0.853334 215.21067,13.312 383.82934,192.341333 383.82934,407.210663 0,91.47734 -31.57334,181.58934 -88.74667,253.44 C 775.50933,756.90667 695.12533,807.936 606.03733,828.928 Z"
                  fill="#9b7bff"
                  id="path2-7" />
                <path
                  d="m 767.488,313.17333 c -12.97067,-6.48533 -217.94133,-99.328 -217.94133,-99.328 0,0 -21.504,-10.92266 -32.42667,-10.92266 -10.92267,0 -32.42667,10.92266 -32.42667,10.92266 0,0 -205.14133,92.84267 -218.112,99.328 -12.97066,6.48534 0,12.97067 0,12.97067 0,0 174.93334,79.70133 207.18934,92.84267 25.94133,10.92266 32.42666,10.92266 43.17866,10.92266 10.92267,0 17.23734,0 43.17867,-10.92266 21.504,-10.92267 112.128,-51.712 166.22933,-75.60534 v 69.12 l -17.23733,17.23734 23.72267,23.72266 23.72266,-23.72266 -17.06666,-17.23734 V 339.11467 C 756.736,332.62933 767.488,326.144 767.488,326.144 c 0,0 12.8,-6.48533 0,-12.97067 z"
                  fill="#9b7bff"
                  id="path3-8-33" />
                <path
                  d="m 517.12,474.624 c -10.92267,0 -17.23733,0 -43.17867,-10.92267 C 458.752,457.216 407.04,433.49333 359.59467,411.98933 v 157.52534 c 47.616,21.504 99.328,45.39733 114.34666,51.712 25.94134,10.92266 32.42667,10.92266 43.17867,10.92266 10.92267,0 17.23733,0 43.17867,-10.92266 15.18933,-6.48534 66.90133,-30.208 114.34666,-51.712 V 411.98933 c -47.616,21.504 -99.328,45.39734 -114.34666,51.712 C 534.35733,476.84267 527.872,474.624 517.12,474.624 Z"
                  fill="#9b7bff"
                  id="path4-2-59" />
              </g>
              <g
                id="g4-26"
                style="overflow:hidden;fill:currentColor"
                transform="matrix(0.01579114,0,0,0.01232821,376.36727,125.00517)">
                <path
                  d="m 537.42933,24.746667 c -9.38666,-0.512 -18.09066,-0.853333 -25.77066,-0.853334 -205.99467,0 -380.416,153.770667 -405.67467,357.546667 -12.458667,100.01067 12.62933,200.704 70.656,283.648 57.856,82.60267 143.53067,140.8 241.32267,163.84 L 428.032,831.31733 512,976.384 595.968,831.31733 606.03733,828.928 c 89.088,-20.992 169.472,-71.85067 226.47467,-143.53067 57.17333,-71.85066 88.74667,-161.792 88.74667,-253.44 0,-215.04 -168.61867,-393.898664 -383.82934,-407.210663 z"
                  fill="#070f1b"
                  id="path1-6" />
                <path
                  d="M 538.79467,0.853333 C 529.92,0.341333 520.704,0 511.65867,0 293.71733,0 109.056,162.64533 82.261333,378.53867 c -27.306667,219.30666 116.565337,423.424 330.239997,473.6 L 475.136,960.34133 512,1024 548.864,960.34133 611.49867,852.13867 C 805.888,806.4 945.152,632.832 945.152,431.95733 945.152,204.288 766.80533,15.018667 538.79467,0.853333 Z M 606.03733,828.928 595.968,831.31733 512,976.384 428.032,831.31733 417.96267,828.928 C 320.17067,805.888 234.496,747.86133 176.64,665.088 118.61333,582.144 93.525333,481.45067 105.984,381.44 131.24267,177.664 305.664,23.893333 511.65867,23.893333 c 7.68,0 16.384,0.341333 25.77066,0.853334 215.21067,13.312 383.82934,192.341333 383.82934,407.210663 0,91.47734 -31.57334,181.58934 -88.74667,253.44 C 775.50933,756.90667 695.12533,807.936 606.03733,828.928 Z"
                  fill="#9b7bff"
                  id="path2-0" />
                <path
                  d="m 767.488,313.17333 c -12.97067,-6.48533 -217.94133,-99.328 -217.94133,-99.328 0,0 -21.504,-10.92266 -32.42667,-10.92266 -10.92267,0 -32.42667,10.92266 -32.42667,10.92266 0,0 -205.14133,92.84267 -218.112,99.328 -12.97066,6.48534 0,12.97067 0,12.97067 0,0 174.93334,79.70133 207.18934,92.84267 25.94133,10.92266 32.42666,10.92266 43.17866,10.92266 10.92267,0 17.23734,0 43.17867,-10.92266 21.504,-10.92267 112.128,-51.712 166.22933,-75.60534 v 69.12 l -17.23733,17.23734 23.72267,23.72266 23.72266,-23.72266 -17.06666,-17.23734 V 339.11467 C 756.736,332.62933 767.488,326.144 767.488,326.144 c 0,0 12.8,-6.48533 0,-12.97067 z"
                  fill="#9b7bff"
                  id="path3-8-38" />
                <path
                  d="m 517.12,474.624 c -10.92267,0 -17.23733,0 -43.17867,-10.92267 C 458.752,457.216 407.04,433.49333 359.59467,411.98933 v 157.52534 c 47.616,21.504 99.328,45.39733 114.34666,51.712 25.94134,10.92266 32.42667,10.92266 43.17867,10.92266 10.92267,0 17.23733,0 43.17867,-10.92266 15.18933,-6.48534 66.90133,-30.208 114.34666,-51.712 V 411.98933 c -47.616,21.504 -99.328,45.39734 -114.34666,51.712 C 534.35733,476.84267 527.872,474.624 517.12,474.624 Z"
                  fill="#9b7bff"
                  id="path4-2-0" />
              </g>
              <g
                id="g4-9"
                style="overflow:hidden;fill:currentColor"
                transform="matrix(0.01579114,0,0,0.01232821,327.46171,101.48124)">
                <path
                  d="m 537.42933,24.746667 c -9.38666,-0.512 -18.09066,-0.853333 -25.77066,-0.853334 -205.99467,0 -380.416,153.770667 -405.67467,357.546667 -12.458667,100.01067 12.62933,200.704 70.656,283.648 57.856,82.60267 143.53067,140.8 241.32267,163.84 L 428.032,831.31733 512,976.384 595.968,831.31733 606.03733,828.928 c 89.088,-20.992 169.472,-71.85067 226.47467,-143.53067 57.17333,-71.85066 88.74667,-161.792 88.74667,-253.44 0,-215.04 -168.61867,-393.898664 -383.82934,-407.210663 z"
                  fill="#070f1b"
                  id="path1-4" />
                <path
                  d="M 538.79467,0.853333 C 529.92,0.341333 520.704,0 511.65867,0 293.71733,0 109.056,162.64533 82.261333,378.53867 c -27.306667,219.30666 116.565337,423.424 330.239997,473.6 L 475.136,960.34133 512,1024 548.864,960.34133 611.49867,852.13867 C 805.888,806.4 945.152,632.832 945.152,431.95733 945.152,204.288 766.80533,15.018667 538.79467,0.853333 Z M 606.03733,828.928 595.968,831.31733 512,976.384 428.032,831.31733 417.96267,828.928 C 320.17067,805.888 234.496,747.86133 176.64,665.088 118.61333,582.144 93.525333,481.45067 105.984,381.44 131.24267,177.664 305.664,23.893333 511.65867,23.893333 c 7.68,0 16.384,0.341333 25.77066,0.853334 215.21067,13.312 383.82934,192.341333 383.82934,407.210663 0,91.47734 -31.57334,181.58934 -88.74667,253.44 C 775.50933,756.90667 695.12533,807.936 606.03733,828.928 Z"
                  fill="#9b7bff"
                  id="path2-78" />
                <path
                  d="m 767.488,313.17333 c -12.97067,-6.48533 -217.94133,-99.328 -217.94133,-99.328 0,0 -21.504,-10.92266 -32.42667,-10.92266 -10.92267,0 -32.42667,10.92266 -32.42667,10.92266 0,0 -205.14133,92.84267 -218.112,99.328 -12.97066,6.48534 0,12.97067 0,12.97067 0,0 174.93334,79.70133 207.18934,92.84267 25.94133,10.92266 32.42666,10.92266 43.17866,10.92266 10.92267,0 17.23734,0 43.17867,-10.92266 21.504,-10.92267 112.128,-51.712 166.22933,-75.60534 v 69.12 l -17.23733,17.23734 23.72267,23.72266 23.72266,-23.72266 -17.06666,-17.23734 V 339.11467 C 756.736,332.62933 767.488,326.144 767.488,326.144 c 0,0 12.8,-6.48533 0,-12.97067 z"
                  fill="#9b7bff"
                  id="path3-8-35" />
                <path
                  d="m 517.12,474.624 c -10.92267,0 -17.23733,0 -43.17867,-10.92267 C 458.752,457.216 407.04,433.49333 359.59467,411.98933 v 157.52534 c 47.616,21.504 99.328,45.39733 114.34666,51.712 25.94134,10.92266 32.42667,10.92266 43.17867,10.92266 10.92267,0 17.23733,0 43.17867,-10.92266 15.18933,-6.48534 66.90133,-30.208 114.34666,-51.712 V 411.98933 c -47.616,21.504 -99.328,45.39734 -114.34666,51.712 C 534.35733,476.84267 527.872,474.624 517.12,474.624 Z"
                  fill="#9b7bff"
                  id="path4-2-12" />
              </g>
            </g>
          </svg>
        </div>
      </div>
    </div>
  </div>


  <div id="schoolTooltip" style="position:absolute; display:none; background:#333; color:#fff; padding:5px 10px; border-radius:5px; font-size:14px; pointer-events:none; z-index:1000;"></div>

  <!-- Modal -->
  <div class="modal fade" id="topProblemsModal" tabindex="-1" aria-labelledby="topProblemsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="topProblemsModalLabel">Top 3 Reported Problems</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="modalProblemContent">
          <!-- Dynamic content goes here -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>


  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/sidebar-highlight.js"></script>
  <script>
    const tooltip = document.getElementById("tooltip");

    // Handle <path data-name="...">
    const paths = document.querySelectorAll("svg path");
    paths.forEach(path => {
      path.addEventListener("mousemove", (e) => {
        const name = path.getAttribute("data-name");
        tooltip.textContent = name;
        tooltip.style.left = e.pageX + 10 + "px";
        tooltip.style.top = e.pageY + 10 + "px";
        tooltip.style.display = "block";
      });

      path.addEventListener("mouseleave", () => {
        tooltip.style.display = "none";
      });
    });

    // Handle <g data-school-id="...">
    const groups = document.querySelectorAll("g[data-school-id]");
    let schoolMap = {};

    // Fetch all schools once
    fetch('includes/get_all_schools.php')
      .then(res => res.json())
      .then(data => {
        data.forEach(school => {
          schoolMap[school.school_id] = school.name;
        });

        groups.forEach(g => {
          g.addEventListener("mousemove", (e) => {
            const schoolId = g.getAttribute("data-school-id");
            const name = schoolMap[schoolId];
            if (name) {
              tooltip.textContent = name;
              tooltip.style.left = e.pageX + 10 + "px";
              tooltip.style.top = e.pageY + 10 + "px";
              tooltip.style.display = "block";
            }
          });

          g.addEventListener("mouseleave", () => {
            tooltip.style.display = "none";
          });
        });
      })
      .catch(error => {
        console.error("Failed to fetch school names:", error);
      });
  </script>


  <script>
    const suggestionsMap = {
      "Love Life": "Guidance/Counseling",
      "Bullying": "Guidance/Counseling",
      "Substance Abuse": "Rehabilitation",
      "Mental Health": "Guidance/Counseling",
      "Violence (VAWC)": "Rehabilitation",
      "Too early sexual activity": "Reproductive Health (incl. Family Planning)",
      "Family Problem": "Guidance/Counseling",
      "Peer Pressure": "Information/Education",
      "School Works": "Support for education/technical skills",
      "Medical/Dental": "Medical/Dental",
      "Spiritual Emptiness": "Spiritual Formation"
    };

    document.querySelectorAll("g[data-school-id]").forEach(g => {
      g.addEventListener("click", function() {
        const schoolId = this.getAttribute("data-school-id");

        fetch(`includes/get_top_problems.php?school_id=${schoolId}`)
          .then(response => response.json())
          .then(data => {
            if (!data || !data.top_problems || data.top_problems.length === 0) {
              alert("No assessments for this school yet.");
              return;
            }

            // Build modal content
            let modalContent = `<strong>${data.school_name}</strong><ul>`;
            data.top_problems.forEach((item, index) => {
              modalContent += `<li>${index + 1}. ${item.problem} (${item.count})</li>`;
            });
            modalContent += `</ul><hr>`;

            // Add suggested actions (up to 3 based on top problems)
            modalContent += `<p><strong>Suggested Actions:</strong><ul>`;
            data.top_problems.slice(0, 3).forEach(problemData => {
              const suggestion = suggestionsMap[problemData.problem] || "Guidance/Counseling";
              modalContent += `<li>Offer <em>${suggestion}</em> for "${problemData.problem}"</li>`;
            });
            modalContent += `</ul></p>`;

            // Display modal
            document.getElementById('modalProblemContent').innerHTML = modalContent;
            const topProblemsModal = new bootstrap.Modal(document.getElementById('topProblemsModal'));
            topProblemsModal.show();
          })
          .catch(error => {
            console.error("Error fetching data:", error);
            alert("Failed to fetch problems.");
          });
      });
    });
  </script>

</body>

</html>