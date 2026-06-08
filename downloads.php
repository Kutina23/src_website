<?php
require_once "config/database.php";
require_once "config/functions.php";
require_once "models/Downloads.php";

$db = Database::getInstance();
$downloadsModel = new Downloads($db);

$categories = $downloadsModel->getCategories();
$allDownloads = $downloadsModel->getAllActive();

// Group downloads by category
$downloadsByCategory = [];
foreach ($allDownloads as $download) {
    $downloadsByCategory[$download["category_id"]][] = $download;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Downloads & Documents - Dr. Hilla Limann Technical University</title>
  <meta name="description" content="Download SRC documents, forms, reports, and resources. Access constitution, meeting minutes, financial reports, and more.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&family=Outfit:wght@200;300;400;500;600;700&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
<?php include "include/header.php"; ?>

<!-- Downloads Hero Banner -->
<section id="downloads-hero" style="background: linear-gradient(135deg, #1a3a5c 0%, #0f2440 60%, #0a1628 100%); padding: 120px 40px 60px; margin-top: 0;">
  <div style="max-width: 900px;">
    <h1 style="font-family: 'Cormorant Garamond', serif; font-size: clamp(36px, 5vw, 56px); font-weight: 700; color: #fff; margin: 0 0 16px; line-height: 1.1;">
      Downloads &amp; Documents
    </h1>
    <p style="font-size: 14px; font-weight: 300; color: rgba(255,255,255,0.7); margin: 0; max-width: 480px; line-height: 1.7;">
      Access and download important documents, policies, and circulars from Dr. Hilla Limann Technical University SRC.
    </p>
  </div>
</section>

<!-- Search + Results Count -->
<section id="downloads-content" style="background: #f4f6f8; min-height: 60vh; padding: 40px;">
  <div style="max-width: 1200px; margin: 0 auto;">

    <!-- Search Bar -->
    <div style="margin-bottom: 20px;">
      <div style="position: relative; max-width: 480px;">
        <i class="bi bi-search" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#999; font-size:15px;"></i>
        <input
          type="text"
          id="downloadSearch"
          placeholder="Search downloads by name..."
          oninput="filterDownloads()"
          style="width:100%; padding: 12px 16px 12px 42px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; font-family: 'Outfit', sans-serif; background: #fff; color: #333; outline: none; box-sizing: border-box;"
        >
      </div>
    </div>

    <!-- Count -->
    <p id="downloadCount" style="font-size: 13px; color: #555; margin-bottom: 24px;">
      <?php 
        $totalFiles = 0;
        foreach ($allDownloads as $d) $totalFiles++;
        echo $totalFiles . ' document' . ($totalFiles !== 1 ? 's' : '') . ' found';
      ?>
    </p>

    <!-- Cards Grid -->
    <?php if (empty($allDownloads)): ?>
      <div style="text-align:center; padding:80px 20px; color:#999;">
        <i class="bi bi-folder-x" style="font-size:60px; display:block; margin-bottom:20px; color:#ccc;"></i>
        <p style="font-size:16px;">No downloads available at the moment. Please check back later.</p>
      </div>
    <?php else: ?>
      <div id="downloadsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px;">
        <?php foreach ($allDownloads as $index => $download):
          $ext = strtolower(pathinfo($download["file_path"], PATHINFO_EXTENSION));
          $icon = $downloadsModel->getFileTypeIcon($ext);
          $fileSize = $downloadsModel->getFileSize($download["file_size"] ?? 0);
        ?>
        <div class="dl-card reveal delay-<?php echo ($index % 3) + 1; ?>"
             data-name="<?php echo strtolower(htmlspecialchars($download['title'])); ?>"
             data-file-path="<?php echo htmlspecialchars($download['file_path']); ?>"
             style="background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); display: flex; flex-direction: column; gap: 12px;">

          <!-- Icon + Meta -->
          <div style="display: flex; align-items: flex-start; gap: 14px;">
            <div style="width: 44px; height: 44px; background: #e8f0fe; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
              <i class="bi <?php echo $icon; ?>" style="font-size: 20px; color: #3a6fc4;"></i>
            </div>
            <div style="flex: 1; min-width: 0;">
              <div style="font-size: 14px; font-weight: 600; color: #1a1a2e; line-height: 1.3; word-break: break-word;">
                <?php echo htmlspecialchars($download["title"]); ?>
              </div>
              <div style="font-size: 12px; color: #888; margin-top: 4px;">
                <?php echo strtoupper($ext); ?> &bull; <?php echo $fileSize; ?>
              </div>
            </div>
          </div>

          <!-- Preview Button -->
          <button type="button" onclick="openPreview({title: '<?php echo htmlspecialchars($download["title"], ENT_QUOTES); ?>', file_path: '<?php echo htmlspecialchars($download["file_path"], ENT_QUOTES); ?>'})"
                  style="display: flex; align-items: center; justify-content: center; gap: 8px;
                         background: #1a3a5c; color: #fff; font-weight: 500; font-size: 13px;
                         letter-spacing: 0.03em; border: none; cursor: pointer;
                         padding: 11px 16px; border-radius: 6px;
                         transition: background 0.2s, transform 0.15s;">
            <i class="bi bi-eye" style="font-size: 14px;"></i>
            Preview
          </button>

          <!-- Download Button -->
          <a href="<?php echo htmlspecialchars($download["file_path"]); ?>"
             download
             onclick="incrementDownload(<?php echo $download["id"]; ?>)"
             style="display: flex; align-items: center; justify-content: center; gap: 8px;
                    background: #f0b400; color: #1a1a2e; font-weight: 700; font-size: 13px;
                    letter-spacing: 0.03em; text-decoration: none;
                    padding: 11px 16px; border-radius: 6px;
                    transition: background 0.2s, transform 0.15s;">
            <i class="bi bi-download" style="font-size: 14px;"></i>
            Download File
          </a>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- No results message -->
    <div id="noResults" style="display:none; text-align:center; padding:60px 20px; color:#999;">
      <i class="bi bi-search" style="font-size:48px; display:block; margin-bottom:16px; color:#ccc;"></i>
      <p style="font-size:15px;">No documents match your search.</p>
    </div>

  </div>
</section>

<div id="previewModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:2000;align-items:center;justify-content:center;">
  <div style="background:var(--navy);border:1px solid rgba(201,168,76,0.2);border-radius:12px;max-width:900px;width:90%;max-height:90vh;overflow:hidden;display:flex;flex-direction:column;">
    <div style="padding:20px 24px;border-bottom:1px solid rgba(201,168,76,0.1);display:flex;justify-content:space-between;align-items:center;">
      <h3 id="previewTitle" style="margin:0;font-size:18px;color:var(--cream);"></h3>
      <button onclick="closePreview()" style="background:none;border:none;color:var(--text-muted);font-size:24px;cursor:pointer;">&times;</button>
    </div>
    <div id="previewContent" style="flex:1;overflow:auto;padding:24px;display:flex;align-items:center;justify-content:center;">
      <div id="previewBody"></div>
    </div>
    <div style="padding:16px 24px;border-top:1px solid rgba(201,168,76,0.1);display:flex;gap:12px;justify-content:flex-end;">
      <button onclick="closePreview()" class="btn-outline" style="font-size:12px;padding:8px 16px;">Close</button>
      <a id="downloadBtn" href="#" download class="btn-primary" style="font-size:12px;padding:8px 16px;text-decoration:none;">
        <i class="bi bi-download"></i> Download
      </a>
    </div>
  </div>
</div>

<?php include "include/footer.php"; ?>

<script>
function filterDownloads() {
  var query = document.getElementById('downloadSearch').value.toLowerCase().trim();
  var cards = document.querySelectorAll('.dl-card');
  var visible = 0;
  cards.forEach(function(card) {
    var name = card.getAttribute('data-name') || '';
    if (!query || name.indexOf(query) !== -1) {
      card.style.display = '';
      visible++;
    } else {
      card.style.display = 'none';
    }
  });
  var countEl = document.getElementById('downloadCount');
  if (countEl) countEl.textContent = visible + ' document' + (visible !== 1 ? 's' : '') + ' found';
  var noResults = document.getElementById('noResults');
  if (noResults) noResults.style.display = visible === 0 ? 'block' : 'none';
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  /* ── Reveal animation ───────────────────────────────────────────────── */
  var _observer = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        var el = entry.target;
        var delay = 0;
        if (el.classList.contains('delay-1')) delay = 150;
        else if (el.classList.contains('delay-2')) delay = 300;
        else if (el.classList.contains('delay-3')) delay = 450;
        else if (el.classList.contains('delay-4')) delay = 600;
        setTimeout(function() {
          el.style.opacity   = '1';
          el.style.transform = 'translateY(0)';
        }, delay);
        _observer.unobserve(el);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.reveal').forEach(function(el) {
    el.style.opacity   = '0';
    el.style.transform = 'translateY(40px)';
    el.style.transition = 'opacity 0.9s cubic-bezier(0.16,1,0.3,1), transform 0.9s cubic-bezier(0.16,1,0.3,1)';
_observer.observe(el);
  });

  document.querySelectorAll('.dl-card button[onclick^="openPreview"]').forEach(function(btn) {
    btn.addEventListener('mouseenter', function() {
      this.style.background = '#0f2440';
      this.style.transform  = 'translateY(-1px)';
    });
    btn.addEventListener('mouseleave', function() {
      this.style.background = '#1a3a5c';
      this.style.transform  = '';
    });
  });
  document.querySelectorAll('.dl-card a[download]').forEach(function(btn) {
    btn.addEventListener('mouseenter', function() {
      this.style.background = '#e0a800';
      this.style.transform  = 'translateY(-1px)';
    });
    btn.addEventListener('mouseleave', function() {
      this.style.background = '#f0b400';
      this.style.transform  = '';
    });
  });
});

/* ── Global: card click-to-preview ───────────────────────────────────── */
function openPreview(data) {
  var previewModal = document.getElementById("previewModal");
  var titleEl      = document.getElementById("previewTitle");
  var previewBody  = document.getElementById("previewBody");

  titleEl.textContent        = data.title || "Document Preview";
  document.getElementById("downloadBtn").href   = data.file_path;
  document.getElementById("downloadBtn").download = data.file_path.split("/").pop();

  var ext        = (data.file_path.split(".").pop() || "").toLowerCase();
  var previewUrl = data.file_path;

  if (ext === "pdf") {
    previewBody.innerHTML = '<iframe src="' + previewUrl + '" style="width:100%;height:60vh;border:none;"></iframe>';
  } else if (["jpg","jpeg","png","gif","webp"].includes(ext)) {
    previewBody.innerHTML = '<img src="' + previewUrl + '" style="max-width:100%;max-height:60vh;object-fit:contain;">';
  } else if (["doc","docx"].includes(ext)) {
    previewBody.innerHTML = '<div style="text-align:center;padding:40px;"><i class="bi bi-file-word" style="font-size:60px;color:var(--gold);margin-bottom:16px;"></i><p style="color:var(--text-muted);">Word documents can be downloaded to view.</p></div>';
  } else if (["xls","xlsx"].includes(ext)) {
    previewBody.innerHTML = '<div style="text-align:center;padding:40px;"><i class="bi bi-file-excel" style="font-size:60px;color:var(--gold);margin-bottom:16px;"></i><p style="color:var(--text-muted);">Excel files can be downloaded to view.</p></div>';
  } else if (["ppt","pptx"].includes(ext)) {
    previewBody.innerHTML = '<div style="text-align:center;padding:40px;"><i class="bi bi-file-ppt" style="font-size:60px;color:var(--gold);margin-bottom:16px;"></i><p style="color:var(--text-muted);">PowerPoint files can be downloaded to view.</p></div>';
  } else if (["txt"].includes(ext)) {
    fetch(previewUrl).then(function(r) { return r.text(); }).then(function(content) {
      previewBody.innerHTML = '<pre style="background:var(--navy-dark);padding:20px;border-radius:8px;color:var(--cream);white-space:pre-wrap;word-wrap:break-word;max-height:60vh;overflow:auto;">' + content + '</pre>';
    }).catch(function() {
      previewBody.innerHTML = '<div style="text-align:center;padding:40px;"><i class="bi bi-file-text" style="font-size:60px;color:var(--gold);margin-bottom:16px;"></i><p style="color:var(--text-muted);">Unable to load file content.</p></div>';
    });
  } else {
    previewBody.innerHTML = '<div style="text-align:center;padding:40px;"><i class="bi bi-file-earmark" style="font-size:60px;color:var(--gold);margin-bottom:16px;"></i><p style="color:var(--text-muted);">Preview not available. Please download the file.</p></div>';
  }

  previewModal.style.display  = "flex";
  document.body.style.overflow = "hidden";
}

function closePreview() {
  document.getElementById("previewModal").style.display = "none";
  document.body.style.overflow = "";
}

function incrementDownload(id) {
  fetch("api/downloads.php?action=download&id=" + id, {method: "POST"}).catch(function() {});
}

document.addEventListener("keydown", function(e) {
   if (e.key === "Escape") closePreview();
 });
 document.getElementById("previewModal").addEventListener("click", function(e) {
   if (e.target === this) closePreview();
 });

 /* ── Mobile Menu Toggle ── */
 var mobileToggle = document.querySelector(".mobile-toggle");
 var navList = document.querySelector(".nav-list");
 if (mobileToggle && navList) {
   mobileToggle.addEventListener("click", function() {
     this.classList.toggle("active");
     navList.classList.toggle("active");
   });
   /* Close menu when clicking outside */
   document.addEventListener("click", function(e) {
     if (navList.classList.contains("active") && !mobileToggle.contains(e.target) && !navList.contains(e.target)) {
       mobileToggle.classList.remove("active");
       navList.classList.remove("active");
     }
   });
 }

 /* ── Mobile Dropdown Toggle ── */
 document.querySelectorAll(".nav-item > .nav-link").forEach(function(link) {
   link.addEventListener("click", function(e) {
     var parentItem = this.closest(".nav-item");
     var dropdown = parentItem.querySelector(".dropdown");
     if (dropdown) {
       e.preventDefault();
       parentItem.classList.toggle("open");
       dropdown.classList.toggle("open");
     }
   });
 });
</script>

</body>
</html>