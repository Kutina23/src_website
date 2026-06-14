<?php
// DHLTU SRC — Marketplace Page
// File: marketplace.php
// Purpose: Student marketplace for merchandise, services, and products
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="DHLTU SRC Marketplace - Official SRC merchandise, services, and student products in one place.">
  <meta name="author" content="DHLTU SRC">
  <title>SRC Marketplace — DHLTU Student Representative Council</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&family=Poppins:wght@200;300;400;500;600;700;800&family=Inter:wght@200;300;400;500;600;700&family=Outfit:wght@200;300;400;500;600;700;700&family=Space+Mono:ital@0,400;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>
<div class="mobile-overlay" id="mobileOverlay"></div>

<?php include 'include/header.php'; ?>

<!-- ============================================================
     MARKETPLACE HERO SECTION
     ============================================================ -->
<section class="marketplace-hero" id="marketplace">
  <div class="marketplace-hero-bg"></div>
  <div class="marketplace-hero-inner">
    <div class="marketplace-hero-eyebrow">
      <i class="bi bi-shop"></i> SRC Marketplace
    </div>
    <h1 class="marketplace-hero-title">Welcome to the DHLTU SRC Marketplace</h1>
    <p class="marketplace-hero-lead">
      Official SRC merchandise, services, and student products in one place.
      Support fellow students and get quality campus essentials.
    </p>
    <div class="marketplace-hero-cta">
      <a href="#featured-products" class="btn btn-primary btn-lg"><i class="bi bi-bag"></i> Shop Now</a>
      <a href="#become-vendor" class="btn btn-outline btn-lg"><i class="bi bi-person-badge"></i> Become a Vendor</a>
    </div>
  </div>
<div class="marketplace-hero-image">
      <picture>
        <source srcset="assets/images/banner.webp" type="image/webp">
        <img src="assets/images/banner.png" alt="Students shopping">
      </picture>
   </div>
</section>

<!-- ============================================================
     MARKETPLACE NAVBAR (Sticky)
     ============================================================ -->
<nav class="marketplace-navbar">
   <div class="container" style="display:flex;align-items:center;justify-content:space-between;">
     <div style="display:flex;align-items:center;">
       <span style="font-family:'Space Mono',monospace;font-size:12px;color:var(--gold);letter-spacing:0.1em;text-transform:uppercase;">Categories:</span>
       <select onchange="filterProducts(this.value)" class="marketplace-category-select" style="margin-left:12px;padding:6px 12px;border-radius:4px;border:1px solid rgba(201,168,76,0.3);background:rgba(255,255,255,0.05);color:#000;font-size:13px;cursor:pointer;">
         <option value="all">All</option>
         <option value="merchandise">Merchandise</option>
         <option value="books">Books</option>
         <option value="fashion">Fashion</option>
         <option value="electronics">Electronics</option>
         <option value="food">Food</option>
         <option value="services">Services</option>
       </select>
     </div>
    <div style="display:flex;align-items:center;gap:12px;">
      <div class="marketplace-search-wrapper">
        <input type="text" id="marketplace-search" class="marketplace-search" placeholder="Search products...">
        <div id="search-suggestions"></div>
      </div>
      <a href="#" class="cart-icon"><i class="bi bi-cart"></i><span class="cart-badge">0</span></a>
      <button id="dark-mode-toggle" class="dark-mode-toggle"><i class="bi bi-moon"></i> Dark</button>
     
    </div>
  </div>
</nav>

<div class="cinematic-divider"></div>

<!-- ============================================================
     FEATURED CATEGORIES SECTION
     ============================================================ -->
<section class="marketplace-categories-section" id="categories">
  <div class="container">
    <div class="section-header">
      <div class="marketplace-eyebrow" style="justify-content:center;">
        <i class="bi bi-grid-1x2"></i> Shop by Category
      </div>
      <h2 class="marketplace-section-title">Featured Categories</h2>
    </div>
    <div class="categories-grid">
      <?php 
      $categories = [
        ['name' => 'SRC Merchandise', 'icon' => 'bi-patch-check-fill', 'color' => 'var(--gold)'],
        ['name' => 'Books & Handouts', 'icon' => 'bi-book', 'color' => '#4F8BFF'],
        ['name' => 'Fashion & Apparel', 'icon' => 'bi-bag', 'color' => '#E84C8C'],
        ['name' => 'Electronics', 'icon' => 'bi-laptop', 'color' => '#4CE8A1'],
        ['name' => 'Food & Snacks', 'icon' => 'bi-cup-hot', 'color' => '#E78C4C'],
        ['name' => 'Event Tickets', 'icon' => 'bi-ticket-perforated', 'color' => '#B84CFF'],
        ['name' => 'Student Services', 'icon' => 'bi-gear', 'color' => '#4CCCFF'],
      ];
      foreach ($categories as $index => $cat): ?>
      <a href="marketplace.php?category=<?php echo urlencode($cat['name']); ?>" class="category-card reveal delay-<?php echo ($index % 3) + 1; ?>">
        <div class="category-icon" style="color: <?php echo $cat['color']; ?>;">
          <i class="bi <?php echo $cat['icon']; ?>"></i>
        </div>
        <h3 class="category-title"><?php echo $cat['name']; ?></h3>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<section class="featured-products-section" id="featured-products">
  <div class="container">
    <div class="section-header">
      <div class="marketplace-eyebrow">
        <i class="bi bi-star"></i> Featured Products
      </div>
      <h2 class="marketplace-section-title">Popular Items</h2>
    </div>
    <div class="products-grid">
      <?php 
         $products = [
           ['id' => 'src-hoodie', 'name' => 'SRC Premium Hoodie', 'price' => 'GH₵ 85', 'vendor' => 'SRC Store', 'rating' => '4.8', 'image' => 'hoodie.jpg', 'category' => 'merchandise'],
           ['id' => 'notebook-set', 'name' => 'DHLTU Notebook Set', 'price' => 'GH₵ 25', 'vendor' => 'Campus Supplies', 'rating' => '4.5', 'image' => 'notebook.jpg', 'category' => 'books'],
           ['id' => 'math-handout', 'name' => 'Mathematics Handout', 'price' => 'GH₵ 15', 'vendor' => 'Study Hub', 'rating' => '4.9', 'image' => 'handout.jpg', 'category' => 'books'],
           ['id' => 'wristwatch', 'name' => 'Digital Wristwatch', 'price' => 'GH₵ 120', 'vendor' => 'Tech Store', 'rating' => '4.7', 'image' => 'watch.jpg', 'category' => 'electronics'],
           ['id' => 'waakye', 'name' => 'Waakye Pack', 'price' => 'GH₵ 12', 'vendor' => 'Campus Kitchen', 'rating' => '4.6', 'image' => 'waakye.jpg', 'category' => 'food'],
           ['id' => 'convocation-ticket', 'name' => 'Convocation Ticket', 'price' => 'GH₵ 50', 'vendor' => 'SRC Events', 'rating' => '5.0', 'image' => 'ticket.jpg', 'category' => 'services'],
           ['id' => 'laptop-stand', 'name' => 'Laptop Stand', 'price' => 'GH₵ 45', 'vendor' => 'Tech Store', 'rating' => '4.4', 'image' => 'stand.jpg', 'category' => 'electronics'],
           ['id' => 'src-cap', 'name' => 'SRC Baseball Cap', 'price' => 'GH₵ 35', 'vendor' => 'SRC Store', 'rating' => '4.7', 'image' => 'cap.jpg', 'category' => 'merchandise'],
         ];
         foreach ($products as $index => $product): ?>
        <div class="product-card reveal delay-<?php echo ($index % 3) + 1; ?>" data-category="<?php echo $product['category']; ?>">
          <div class="product-image">
             <img src="assets/images/products/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" onerror="this.src='assets/images/banner.webp'">
          </div>
          <div class="product-info">
            <h3 class="product-name"><?php echo $product['name']; ?></h3>
            <div class="product-meta">
              <span class="product-vendor"><?php echo $product['vendor']; ?></span>
              <span class="product-rating"><i class="bi bi-star-fill"></i> <?php echo $product['rating']; ?></span>
            </div>
            <div class="product-price"><?php echo $product['price']; ?></div>
            <div class="product-actions">
              <button class="btn btn-primary btn-sm" onclick="addToCart('<?php echo $product['id']; ?>')"><i class="bi bi-cart-plus"></i> Add to Cart</button>
              <button class="wishlist-btn" onclick="toggleWishlist('<?php echo $product['id']; ?>', this)"><i class="bi bi-heart"></i></button>
              <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn btn-outline btn-sm">View Details</a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<section class="trending-section" id="trending">
  <div class="container">
    <div class="section-header">
      <div class="marketplace-eyebrow">
        <i class="bi bi-fire"></i> Trending Now
      </div>
      <h2 class="marketplace-section-title">Hot Picks</h2>
    </div>
    <div class="trending-carousel">
      <?php foreach (array_slice($products, 0, 6) as $product): ?>
      <div class="trending-card" data-category="<?php echo $product['category']; ?>">
        <img src="assets/images/products/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" onerror="this.src='assets/images/banner.webp'">
        <div><?php echo $product['name']; ?></div>
        <small><?php echo $product['price']; ?></small>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<section class="become-vendor-section" id="become-vendor">
  <div class="container">
    <div class="section-header">
      <div class="marketplace-eyebrow">
        <i class="bi bi-person-badge"></i> Sell With Us
      </div>
      <h2 class="marketplace-section-title">Become a Vendor</h2>
    </div>
    <div class="vendor-signup-content" style="max-width:600px;margin:0 auto;text-align:center;">
      <p style="font-size:16px;color:var(--text-muted);margin-bottom:24px;line-height:1.8;">
        Are you a student entrepreneur or service provider? Join our marketplace to showcase your products to the entire DHLTU community.
      </p>
      <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
        <a href="portal/register-vendor.php" class="btn btn-primary"><i class="bi bi-person-plus"></i> Register as Vendor</a>
        <a href="vendor-guidelines.php" class="btn btn-outline"><i class="bi bi-file-text"></i> View Guidelines</a>
      </div>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<section class="vendor-highlight-section" id="vendors">
  <div class="container">
    <div class="section-header">
      <div class="marketplace-eyebrow">
        <i class="bi bi-shop"></i> Top Vendors
      </div>
      <h2 class="marketplace-section-title">Student Sellers</h2>
    </div>
    <div class="vendors-grid">
      <?php 
      $vendors = [
        ['name' => 'SRC Official Store', 'desc' => 'Official DHLTU SRC merchandise and branded items.', 'image' => 'src-store.jpg'],
        ['name' => 'Campus Supplies', 'desc' => 'All your academic supplies and stationery needs.', 'image' => 'campus-supplies.jpg'],
        ['name' => 'Study Hub', 'desc' => 'Notes, handouts, and study materials from top students.', 'image' => 'study-hub.jpg'],
      ];
      foreach ($vendors as $vendor): ?>
      <div class="vendor-card reveal">
        <img src="assets/images/vendors/<?php echo $vendor['image']; ?>" alt="<?php echo $vendor['name']; ?>" class="vendor-image" onerror="this.src='assets/images/banner.webp'">
        <div class="vendor-info">
          <h3><?php echo $vendor['name']; ?></h3>
          <p><?php echo $vendor['desc']; ?></p>
          <a href="vendor.php?name=<?php echo urlencode($vendor['name']); ?>" class="btn btn-outline btn-sm">Visit Store</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<section class="why-shop-section" id="why-shop">
  <div class="container">
    <div class="section-header">
      <div class="marketplace-eyebrow">
        <i class="bi bi-shield-check"></i> Why Choose Us
      </div>
      <h2 class="marketplace-section-title">Why Shop With Us</h2>
    </div>
    <div class="features-grid">
      <div class="feature-card reveal delay-1">
        <i class="bi bi-shield-lock feature-icon"></i>
        <h3>Secure Payments</h3>
        <p>Safe and protected transactions with multiple payment options</p>
      </div>
      <div class="feature-card reveal delay-2">
        <i class="bi bi-patch-check feature-icon"></i>
        <h3>Verified Vendors</h3>
        <p>All sellers are verified DHLTU students and staff</p>
      </div>
      <div class="feature-card reveal delay-3">
        <i class="bi bi-truck feature-icon"></i>
        <h3>Fast Delivery</h3>
        <p>Quick campus delivery within 24 hours</p>
      </div>
      <div class="feature-card reveal delay-1">
        <i class="bi bi-tag feature-icon"></i>
        <h3>Affordable Prices</h3>
        <p>Best prices with student-friendly discounts</p>
      </div>
      <div class="feature-card reveal delay-2">
        <i class="bi bi-award feature-icon"></i>
        <h3>SRC Trusted</h3>
        <p>Official SRC platform with quality assurance</p>
      </div>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<section class="testimonials-section" id="testimonials">
  <div class="container">
    <div class="section-header">
      <div class="marketplace-eyebrow">
        <i class="bi bi-chat-quote"></i> Student Voices
      </div>
      <h2 class="marketplace-section-title">What Students Say</h2>
    </div>
    <div class="testimonials-grid">
      <?php 
      $testimonials = [
        ['name' => 'Amina S.', 'year' => 'Computer Science', 'quote' => 'Got my textbooks and SRC hoodie in one place. Awesome service!', 'rating' => 5],
        ['name' => 'Kwame B.', 'year' => 'Engineering', 'quote' => 'Campus delivery is super fast. Highly recommend!', 'rating' => 5],
        ['name' => 'Fatima M.', 'year' => 'Business', 'quote' => 'Great prices and quality items. Love the variety.', 'rating' => 4],
      ];
      foreach ($testimonials as $testimonial): ?>
      <div class="testimonial-card reveal">
        <div class="testimonial-content">
          <i class="bi bi-quote testimonial-quote"></i>
          <p><?php echo $testimonial['quote']; ?></p>
        </div>
        <div class="testimonial-author">
          <div class="testimonial-name"><?php echo $testimonial['name']; ?></div>
          <div class="testimonial-year"><?php echo $testimonial['year']; ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<section class="statistics-section" id="statistics">
  <div class="container">
    <div class="stats-grid">
      <div class="stat-card reveal delay-1">
        <div class="stat-number" data-count="5000">0</div>
        <div class="stat-label">Registered Students</div>
      </div>
      <div class="stat-card reveal delay-2">
        <div class="stat-number" data-count="250">0</div>
        <div class="stat-label">Products Available</div>
      </div>
      <div class="stat-card reveal delay-3">
        <div class="stat-number" data-count="45">0</div>
        <div class="stat-label">Vendors</div>
      </div>
      <div class="stat-card reveal delay-1">
        <div class="stat-number" data-count="1500">0</div>
        <div class="stat-label">Orders Delivered</div>
      </div>
    </div>
  </div>
</section>

<div class="cinematic-divider"></div>

<section class="newsletter-section" id="newsletter">
  <div class="container">
    <div class="newsletter-content">
      <h2>Stay Updated</h2>
      <p>Subscribe to get announcements, new products, and special offers</p>
      <form id="newsletter-form" class="newsletter-form">
        <input type="email" name="email" placeholder="Your email address" required>
        <button type="submit" class="btn btn-primary"><i class="bi bi-envelope"></i> Subscribe</button>
      </form>
    </div>
  </div>
</section>

<?php include 'include/footer.php'; ?>

<style>
:root {
  --gold: #C9A84C;
  --gold-light: #E8C97A;
  --gold-dark: #8B6914;
  --navy: #0A1628;
  --navy-mid: #0F2040;
  --navy-light: #1A3060;
  --cream: #F5F0E8;
  --text-muted: #8A9BB8;
}

.marketplace-hero {
  background: linear-gradient(135deg, var(--navy-mid) 0%, var(--navy) 100%);
  padding: 180px 40px 100px;
  position: relative;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
  align-items: center;
}
.marketplace-eyebrow {
  font-family: 'Space Mono', monospace;
  font-size: 10px;
  letter-spacing: 0.25em;
  text-transform: uppercase;
  color: var(--gold);
  display: inline-flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 20px;
}
.marketplace-eyebrow-center { justify-content: center; }
.marketplace-eyebrow-center::before,
.marketplace-eyebrow-center::after {
  content: '';
  width: 30px;
  height: 1px;
  background: var(--gold);
}
.marketplace-eyebrow::before {
  content: '';
  width: 30px;
  height: 1px;
  background: var(--gold);
}
.marketplace-section-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: clamp(36px, 4vw, 48px);
  font-weight: 300;
  color: var(--cream);
  margin-bottom: 24px;
  line-height: 1.1;
}
.section-header { margin-bottom: 40px; }
.marketplace-categories-section,
.featured-products-section,
.trending-section,
.become-vendor-section,
.vendor-highlight-section,
.why-shop-section,
.testimonials-section,
.statistics-section,
.newsletter-section {
  padding: 120px 80px;
}
.marketplace-hero::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at 50% 30%, rgba(201,168,76,0.10) 0%, transparent 70%);
}
.marketplace-hero-inner { position: relative; z-index: 1; }

.marketplace-hero-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: clamp(42px, 5vw, 64px);
  font-weight: 300;
  color: var(--cream);
  margin-bottom: 24px;
  line-height: 1.1;
}

.marketplace-hero-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: clamp(42px, 5vw, 64px);
  font-weight: 300;
  color: var(--cream);
  margin-bottom: 24px;
  line-height: 1.1;
}

.marketplace-hero-lead {
  font-size: 16px;
  color: var(--text-muted);
  max-width: 500px;
  margin-bottom: 32px;
  line-height: 1.7;
}

.marketplace-hero-cta { display: flex; gap: 16px; flex-wrap: wrap; }
.marketplace-hero-image img { width: 100%; height: auto; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }

.categories-grid {
   display: grid;
   grid-template-columns: repeat(4, 1fr);
   gap: 20px;
   margin-top: 40px;
 }
  .category-card {
   background: linear-gradient(135deg, var(--navy-light), var(--navy-mid));
   border: 1px solid rgba(201,168,76,0.15);
   border-radius: 12px;
   padding: 28px;
   text-align: center;
   text-decoration: none;
   transition: all 0.4s cubic-bezier(0.16,1,0.3,1);
   position: relative;
   overflow: hidden;
 }
 .category-card::before {
   content: '';
   position: absolute;
   inset: 0;
   background: radial-gradient(circle at 50% 0%, rgba(201,168,76,0.15), transparent 70%);
   opacity: 0;
   transition: opacity 0.4s ease;
 }
 .category-card:hover { transform: translateY(-8px) scale(1.02); border-color: var(--gold); box-shadow: 0 20px 50px rgba(201,168,76,0.25); }
 .category-card:hover::before { opacity: 1; }
 .category-icon { font-size: 36px; margin-bottom: 16px; transition: transform 0.4s ease; }
 .category-card:hover .category-icon { transform: scale(1.1) rotate(5deg); }
 .category-title { font-size: 15px; font-weight: 600; color: var(--cream); }

.products-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
  margin-top: 40px;
}
.product-card {
  background: var(--navy-mid);
  border: 1px solid rgba(201,168,76,0.12);
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.3s ease;
}
.product-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0,0,0,0.25); }
.product-image img { width: 100%; height: 180px; object-fit: cover; }
.product-info { padding: 16px; }
.product-name { font-size: 16px; font-weight: 600; color: var(--cream); margin-bottom: 8px; }
.product-meta { display: flex; justify-content: space-between; font-size: 12px; color: var(--text-muted); margin-bottom: 12px; }
.product-price { font-size: 18px; font-weight: 700; color: var(--gold-light); margin-bottom: 12px; }
.product-actions { display: flex; gap: 8px; }

.trending-carousel {
  display: flex;
  overflow-x: auto;
  gap: 20px;
  padding: 20px 0;
}
.trending-card {
  min-width: 200px;
  background: var(--navy-mid);
  border-radius: 8px;
  padding: 12px;
  text-align: center;
  flex-shrink: 0;
}
.trending-card img { width: 100%; height: 120px; object-fit: cover; border-radius: 6px; margin-bottom: 8px; }

.vendors-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
  margin-top: 40px;
}
.vendor-card {
  background: var(--navy-mid);
  border: 1px solid rgba(201,168,76,0.12);
  border-radius: 12px;
  padding: 24px;
  display: flex;
  gap: 16px;
  align-items: center;
}
.vendor-image { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; }
.vendor-info h3 { font-size: 16px; color: var(--cream); margin-bottom: 6px; }
.vendor-info p { font-size: 13px; color: var(--text-muted); margin-bottom: 12px; }

.features-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
  margin-top: 40px;
}
.feature-card {
  background: var(--navy-mid);
  border: 1px solid rgba(201,168,76,0.12);
  border-radius: 12px;
  padding: 32px 24px;
  text-align: center;
}
.feature-icon { font-size: 42px; color: var(--gold); margin-bottom: 16px; }
.feature-card h3 { font-size: 18px; color: var(--cream); margin-bottom: 10px; }
.feature-card p { font-size: 14px; color: var(--text-muted); }

.testimonials-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
  margin-top: 40px;
}
.testimonial-card {
  background: var(--navy-mid);
  border: 1px solid rgba(201,168,76,0.12);
  border-radius: 12px;
  padding: 28px;
}
.testimonial-quote { font-size: 32px; color: var(--gold); opacity: 0.3; margin-bottom: 12px; }
.testimonial-content { margin-bottom: 16px; }
.testimonial-author {
  padding-top: 16px;
  border-top: 1px solid rgba(201,168,76,0.1);
}
.testimonial-name { font-size: 14px; font-weight: 600; color: var(--cream); }
.testimonial-year { font-size: 12px; color: var(--text-muted); }

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
  margin-top: 40px;
}
.stat-card {
  background: var(--navy-mid);
  border: 1px solid rgba(201,168,76,0.12);
  border-radius: 12px;
  padding: 32px;
  text-align: center;
}
.stat-number { font-size: 42px; font-weight: 700; color: var(--gold-light); }
.stat-label { font-size: 13px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; }

.newsletter-section {
  background: linear-gradient(135deg, var(--navy-mid) 0%, var(--navy) 100%);
  text-align: center;
}
.newsletter-content h2 { font-size: 32px; color: var(--cream); margin-bottom: 12px; }
.newsletter-content p { color: var(--text-muted); margin-bottom: 20px; }
.newsletter-content { padding: 80px 40px; }
.newsletter-form { margin-top: 20px; display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; }
  .newsletter-form input { padding: 12px 20px; border-radius: 6px; border: 1px solid var(--gold); background: transparent; color: var(--cream); min-width: 280px; }

  /* MarketPlace Sticky Navbar */
  .marketplace-navbar {
    position: sticky;
    top: 123px;
    background: rgba(15, 32, 64, 0.95);
    backdrop-filter: blur(16px);
    padding: 16px 40px;
    z-index: 700;
    border-bottom: 1px solid rgba(201,168,76,0.1);
  }
  .marketplace-navbar .btn-sm {
    font-size: 10px;
    padding: 6px 12px;
  }

  .btn-lg { padding: 14px 28px; font-size: 14px; }
  .btn-sm { padding: 8px 16px; font-size: 12px; }

@media (max-width: 1100px) {
   .categories-grid,.products-grid,.vendors-grid,.features-grid,.testimonials-grid,.stats-grid,.marketplace-categories-section,
   .featured-products-section,.trending-section,.become-vendor-section,.vendor-highlight-section,.why-shop-section,
   .testimonials-section,.statistics-section,.newsletter-section { padding: 100px 40px; }
   .marketplace-hero { grid-template-columns: 1fr; padding: 140px 20px 60px; }
   .marketplace-navbar { padding: 12px 20px; }
   .marketplace-search { width: 140px; }
 }

@media (max-width: 768px) {
   .marketplace-categories-section,
   .featured-products-section,
   .trending-section,
   .become-vendor-section,
   .vendor-highlight-section,
   .why-shop-section,
   .testimonials-section,
   .statistics-section,
   .newsletter-section { padding: 60px 20px; }
   .categories-grid { grid-template-columns: 1fr; }
   .products-grid { grid-template-columns: 1fr; }
   .features-grid { grid-template-columns: 1fr; }
   .stats-grid { grid-template-columns: 1fr; }
   .marketplace-navbar .container { flex-direction: column; gap: 12px; }
   .marketplace-navbar > div { flex-wrap: wrap; justify-content: center; }
 }
</style>



<!-- Dark Mode Styles -->
<style>
.dark-mode {
  --gold: #C9A84C;
  --gold-light: #E8C97A;
  --gold-dark: #8B6914;
  --navy: #0A1628;
  --navy-mid: #0F2040;
  --navy-light: #1A3060;
  --cream: #0A1628;
  --text-muted: #8A9BB8;
}
.dark-mode .product-card,
.dark-mode .category-card,
.dark-mode .vendor-card,
.dark-mode .feature-card,
.dark-mode .testimonial-card,
.dark-mode .stat-card {
  background: var(--navy-light);
}
.wishlist-btn {
  background: transparent;
  border: 1px solid rgba(201,168,76,0.4);
  color: var(--gold);
  padding: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  border-radius: 4px;
}
.wishlist-btn:hover {
  background: rgba(201,168,76,0.1);
}
.wishlist-btn.active {
  color: #e74c3c;
  border-color: #e74c3c;
}
.wishlist-btn.active i {
  color: #e74c3c;
}
#search-suggestions {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: var(--navy);
  border: 1px solid rgba(201,168,76,0.3);
  border-radius: 4px;
  z-index: 1000;
  max-height: 200px;
  overflow-y: auto;
  display: none;
}
.suggestion-item {
  padding: 10px 16px;
  cursor: pointer;
  color: var(--cream);
  font-size: 13px;
}
.marketplace-category-select {
   appearance: none;
   background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23C9A84C'%3E%3Cpath d='M7.247 11.14 7.293 11.2a1 1 0 0 0 1.414 0L8.75 9.707l1.439 1.44a1 1 0 0 0 .707.293 1 1 0 0 0 .707-.293l2.293-2.293a1 1 0 0 0 0-1.414L11.293 5.3a1 1 0 0 0-.707-.293H8.707a1 1 0 0 0-.707.293L5.293 8.293a1 1 0 0 0 0 1.414l2.293 2.293a1 1 0 0 0 .707.293h-.006a1 1 0 0 0 .707-.293l.046-.053z'/%3E%3C/svg%3E");
   background-repeat: no-repeat;
   background-position: right 8px center;
   background-size: 16px;
   padding-right: 32px;
 }
.marketplace-search-wrapper {
  position: relative;
  margin-left: 20px;
}
.marketplace-search {
  padding: 6px 12px;
  border-radius: 4px;
  border: 1px solid rgba(201,168,76,0.3);
  background: rgba(255,255,255,0.05);
  color: var(--cream);
  font-size: 12px;
  width: 180px;
}
.cart-icon {
  position: relative;
  margin-left: 16px;
  color: var(--cream);
  font-size: 18px;
}
.cart-badge {
  position: absolute;
  top: -8px;
  right: -8px;
  background: var(--gold);
  color: var(--navy);
  font-size: 10px;
  font-weight: 600;
  padding: 2px 6px;
  border-radius: 10px;
}
.dark-mode-toggle {
   margin-left: 16px;
   padding: 6px 12px;
   background: transparent;
   border: 1px solid rgba(201,168,76,0.3);
   color: var(--cream);
   cursor: pointer;
   font-size: 12px;
   border-radius: 4px;
  }
  .dark-mode-toggle:hover {
   background: rgba(201,168,76,0.1);
   color: var(--gold);
  }

  /* Mobile Overlay for header nav */
  .mobile-overlay {
   display: none;
   position: fixed;
   inset: 0;
   background: rgba(0, 0, 0, 0.5);
   z-index: 750;
   backdrop-filter: blur(4px);
  }
  .mobile-overlay.active {
   display: block;
  }
</style>
</body>
</html>