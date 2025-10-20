<?php
require 'db.php';
require 'session_helper.php';
include 'header.php';
?>

<main class="container mt-5">
  <!-- Hero -->
  <section class="mb-5">
    <div class="row align-items-center g-4">
      <div class="col-12 col-lg-6">
        <h1 class="fw-bold" style="line-height:1.2">About <span class="text-success">FarmBridge AI</span></h1>
        <p class="text-muted mt-3">
          We connect farmers and buyers with trusted market access, real-time insights, and secure escrow payments.
          Our mission is to empower Rwandan agriculture using technology that is simple, transparent, and fair.
        </p>
        <div class="d-flex gap-2 mt-3">
          <a href="crops.php" class="btn btn-success"><i class="bi bi-shop"></i> Explore Marketplace</a>
          <a href="register.php" class="btn btn-outline-success"><i class="bi bi-person-plus"></i> Join Us</a>
        </div>
      </div>
      <div class="col-12 col-lg-6">
        <div class="ratio ratio-16x9 rounded" style="background:#f5f7f6;display:flex;align-items:center;justify-content:center;border:1px solid #e9ecef">
          <div class="text-center p-4">
            <i class="bi bi-graph-up-arrow text-success" style="font-size:2.2rem"></i>
            <h5 class="mt-2 mb-1">Data-driven Agriculture</h5>
            <p class="text-muted mb-0 small">Real-time prices, demand forecasts, and AI assistance for smarter decisions.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Mission / Values -->
  <section class="mb-5">
    <div class="row g-4">
      <div class="col-12 col-lg-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body">
            <h5 class="fw-semibold"><i class="bi bi-bullseye text-success"></i> Our Mission</h5>
            <p class="text-muted mb-0">Empower farmers, inform buyers, and strengthen food systems by reducing frictions in agricultural trade.</p>
          </div>
        </div>
      </div>
      <div class="col-12 col-lg-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body">
            <h5 class="fw-semibold"><i class="bi bi-shield-check text-success"></i> What We Provide</h5>
            <ul class="text-muted mb-0">
              <li>Secure escrow payments</li>
              <li>Trusted farmer-buyer marketplace</li>
              <li>Price intelligence and demand forecasts</li>
              <li>AI assistant for farming and market help</li>
            </ul>
          </div>
        </div>
      </div>
      <div class="col-12 col-lg-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body">
            <h5 class="fw-semibold"><i class="bi bi-heart text-success"></i> Our Values</h5>
            <ul class="text-muted mb-0">
              <li>Transparency and fairness</li>
              <li>Farmer-first design</li>
              <li>Reliability and simplicity</li>
              <li>Community impact</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Impact Stats -->
  <section class="mb-5">
    <div class="row g-3 text-center">
      <div class="col-6 col-lg-3">
        <div class="p-3 border rounded-3">
          <div class="fs-4 fw-bold text-success">Secure</div>
          <div class="text-muted small">Escrow payments</div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="p-3 border rounded-3">
          <div class="fs-4 fw-bold text-success">Real-time</div>
          <div class="text-muted small">Market insights</div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="p-3 border rounded-3">
          <div class="fs-4 fw-bold text-success">AI</div>
          <div class="text-muted small">Assistant support</div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="p-3 border rounded-3">
          <div class="fs-4 fw-bold text-success">Inclusive</div>
          <div class="text-muted small">Farmer & buyer-first</div>
        </div>
      </div>
    </div>
  </section>

  <!-- How It Works -->
  <section class="mb-5">
    <h4 class="fw-semibold mb-3">How it works</h4>
    <div class="row g-4">
      <div class="col-12 col-lg-4">
        <div class="border rounded-3 p-3 h-100">
          <div class="fw-semibold mb-1">1) List or Browse</div>
          <div class="text-muted small">Farmers list produce; buyers browse verified listings with transparent pricing.</div>
        </div>
      </div>
      <div class="col-12 col-lg-4">
        <div class="border rounded-3 p-3 h-100">
          <div class="fw-semibold mb-1">2) Escrow & Deliver</div>
          <div class="text-muted small">Payment goes to escrow; farmer prepares and delivers; buyer confirms delivery.</div>
        </div>
      </div>
      <div class="col-12 col-lg-4">
        <div class="border rounded-3 p-3 h-100">
          <div class="fw-semibold mb-1">3) Insights & AI</div>
          <div class="text-muted small">Access price intelligence, demand forecasts, and AI guidance anytime.</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Team -->
  <section class="mb-5" id="team">
    <h4 class="fw-semibold mb-3">Our Team</h4>
    <p class="text-muted mb-4">Meet the people building FarmBridge AI. We’re a small, focused team passionate about agriculture, data and design.</p>
    <div class="row g-4">
      <!-- Owner/Founder -->
      <div class="col-12 col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body d-flex flex-column">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width:64px;height:64px;">
                <i class="bi bi-person-fill" style="font-size:1.8rem"></i>
              </div>
              <div>
                <div class="fw-semibold">Bertin Hakizayezu</div>
                <div class="text-muted small">Founder & Product Lead</div>
              </div>
            </div>
            <p class="text-muted small mb-3">Driving product vision, partnerships and community impact for Rwandan agriculture.</p>
            <div class="mt-auto d-flex gap-2">
              <a href="https://www.instagram.com/bertin4real/" target="_blank" class="text-decoration-none"><i class="bi bi-instagram"></i></a>
              <a href="https://www.linkedin.com/in/bertin-hakizayezu-217a79355" target="_blank" class="text-decoration-none"><i class="bi bi-linkedin"></i></a>
              <a href="https://www.youtube.com/@bertinoficial8541" target="_blank" class="text-decoration-none"><i class="bi bi-youtube"></i></a>
            </div>
          </div>
        </div>
      </div>

      <!-- Lead Engineer -->
      <div class="col-12 col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body d-flex flex-column">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width:64px;height:64px;">
                <i class="bi bi-cpu" style="font-size:1.6rem"></i>
              </div>
              <div>
                <div class="fw-semibold">Lead Engineer</div>
                <div class="text-muted small">Full‑stack & AI</div>
              </div>
            </div>
            <p class="text-muted small mb-3">Architecture, data pipelines, and AI integrations powering the marketplace and insights.</p>
            <div class="mt-auto d-flex gap-2">
              <a href="#" class="text-decoration-none"><i class="bi bi-github"></i></a>
              <a href="#" class="text-decoration-none"><i class="bi bi-linkedin"></i></a>
            </div>
          </div>
        </div>
      </div>

      <!-- Design/Community -->
      <div class="col-12 col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body d-flex flex-column">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width:64px;height:64px;">
                <i class="bi bi-brush" style="font-size:1.6rem"></i>
              </div>
              <div>
                <div class="fw-semibold">Design & Community</div>
                <div class="text-muted small">UX & Farmer Success</div>
              </div>
            </div>
            <p class="text-muted small mb-3">Human‑centred design and farmer outreach to ensure the platform is simple and helpful.</p>
            <div class="mt-auto d-flex gap-2">
              <a href="#" class="text-decoration-none"><i class="bi bi-twitter-x"></i></a>
              <a href="#" class="text-decoration-none"><i class="bi bi-link-45deg"></i></a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="mb-5 text-center">
    <div class="p-4 rounded-3" style="background:#f5f7f6;border:1px solid #e9ecef">
      <h5 class="mb-2">Ready to get started?</h5>
      <p class="text-muted">Join FarmBridge AI and grow with confidence.</p>
      <a href="register.php" class="btn btn-success"><i class="bi bi-rocket"></i> Create an account</a>
    </div>
  </section>
</main>

<?php include 'footer.php'; ?>


