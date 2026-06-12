<?php
require_once "config/database.php";
require_once "config/functions.php";
require_once "models/Downloads.php";

$db = Database::getInstance();
$downloadsModel = new Downloads($db);

$allDownloads = $downloadsModel->getAllActive();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Downloads & Documents - Dr. Hilla Limann Technical University</title>
  <meta name="description" content="Download SRC documents, forms, reports, and resources.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&family=Outfit:wght@200;300;400;500;600;700&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="icon" type="image/png" href="assets/images/logo.png">
  <style>
    .dl-card{background:#fff;border-radius:8px;padding:20px;box-shadow:0 1px 4px rgba(0,0,0,.08);display:flex;flex-direction:column;gap:12px;}
    .dl-card .btn{display:flex;align-items:center;justify-content:center;gap:8px;padding:11px 16px;border-radius:6px;border:none;cursor:pointer;font-family:'Outfit',sans-serif;font-size:13px;font-weight:500;letter-spacing:.03em;transition:background .2s,transform .15s;text-decoration:none;}
    .btn-preview{background:#1a3a5c;color:#fff;}
    .btn-preview:hover{background:#0f2440;transform:translateY(-1px);}
    .btn-download{background:#f0b400;color:#1a1a2e;font-weight:700;}
    .btn-download:hover{background:#e0a800;transform:translateY(-1px);}
    #previewModal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(10,22,40,.92);z-index:2000;align-items:center;justify-content:center;padding:24px;box-sizing:border-box;animation:previewFadeIn .2s ease;}
    @keyframes previewFadeIn{from{opacity:0}to{opacity:1}}
    #previewModal .preview-box{background:var(--navy-mid);border:1px solid rgba(201,168,76,.2);border-radius:8px;max-width:1000px;width:100%;max-height:88vh;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 24px 80px rgba(0,0,0,.45);}
    #previewModal .preview-header{background:rgba(201,168,76,.06);padding:18px 24px;border-bottom:1px solid rgba(201,168,76,.12);display:flex;justify-content:space-between;align-items:center;gap:16px;}
    #previewModal .preview-header h3{margin:0;font-size:16px;font-weight:600;color:var(--cream);display:flex;align-items:center;gap:10px;line-height:1.3;word-break:break-word;}
    #previewModal .preview-header h3 i{font-size:18px;color:var(--accent-red);}
    #previewModal .preview-close{background:none;border:none;color:var(--text-muted);font-size:30px;cursor:pointer;line-height:1;padding:4px 8px;transition:color .2s;}
    #previewModal .preview-close:hover{color:var(--cream);}
    #previewModal .preview-body{flex:1;overflow:auto;padding:0;display:flex;align-items:center;justify-content:center;background:var(--navy);min-height:320px;}
    #previewModal .preview-body iframe{width:100%;height:72vh;border:none;background:#fff;}
    #previewModal .preview-body img{max-width:100%;max-height:75vh;object-fit:contain;padding:20px;}
    #previewModal .preview-body pre{background:rgba(10,22,40,.6);border:1px solid rgba(201,168,76,.08);padding:20px;border-radius:6px;color:var(--cream);white-space:pre-wrap;word-wrap:break-word;max-height:75vh;overflow:auto;width:100%;box-sizing:border-box;font-family:'Space Mono',monospace;font-size:13px;line-height:1.6;tab-size:4;}
    #previewModal .preview-body .placeholder{text-align:center;padding:48px 24px;color:var(--text-muted);display:flex;flex-direction:column;align-items:center;gap:14px;}
    #previewModal .preview-body .placeholder i{font-size:52px;color:var(--gold);display:block;}
    #previewModal .preview-body .placeholder p{margin:0;font-size:14px;line-height:1.6;max-width:360px;}
    #previewModal .preview-footer{padding:14px 24px;border-top:1px solid rgba(201,168,76,.1);display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;background:rgba(201,168,76,.03);}
    .preview-spinner{width:36px;height:36px;border:3px solid rgba(201,168,76,.25);border-top-color:var(--gold);border-radius:50%;animation:spin .8s linear infinite;}
    @keyframes spin{to{transform:rotate(360deg)}}
  </style>
</head>
<body>
<?php include "include/header.php"; ?>

<section id="downloads-hero" style="background:linear-gradient(135deg,#1a3a5c 0%,#0f2440 60%,#0a1628 100%);padding:120px 40px 60px;margin-top:0;">
  <div style="max-width:900px;">
    <h1 style="font-family:'Cormorant Garamond',serif;font-size:clamp(36px,5vw,56px);font-weight:700;color:#fff;margin:0 0 16px;line-height:1.1;">Downloads &amp; Documents</h1>
    <p style="font-size:14px;font-weight:300;color:rgba(255,255,255,.7);margin:0;max-width:480px;line-height:1.7;">Access and download important documents, policies, and circulars from Dr. Hilla Limann Technical University SRC.</p>
  </div>
</section>

<section id="downloads-content" style="background:#f4f6f8;min-height:60vh;padding:40px;">
  <div style="max-width:1200px;margin:0 auto;">
    <div style="margin-bottom:20px;">
      <div style="position:relative;max-width:480px;">
        <i class="bi bi-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#999;font-size:15px;"></i>
        <input type="text" id="downloadSearch" placeholder="Search downloads by name..." style="width:100%;padding:12px 16px 12px 42px;border:1px solid #ddd;border-radius:6px;font-size:14px;font-family:'Outfit',sans-serif;background:#fff;color:#333;outline:none;box-sizing:border-box;">
      </div>
    </div>
    <p id="downloadCount" style="font-size:13px;color:#555;margin-bottom:24px;">
      <?php echo count($allDownloads) . ' document' . (count($allDownloads) !== 1 ? 's' : '') . ' found'; ?>
    </p>
    <?php if (empty($allDownloads)): ?>
      <div style="text-align:center;padding:80px 20px;color:#999;">
        <i class="bi bi-folder-x" style="font-size:60px;display:block;margin-bottom:20px;color:#ccc;"></i>
        <p style="font-size:16px;">No downloads available at the moment. Please check back later.</p>
      </div>
    <?php else: ?>
      <div id="downloadsGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:20px;">
        <?php foreach ($allDownloads as $download):
          $ext = strtolower(pathinfo($download["file_path"], PATHINFO_EXTENSION));
          $icon = $downloadsModel->getFileTypeIcon($ext);
          $fileSize = $downloadsModel->getFileSize($download["file_size"] ?? 0);
          $previewUrl = htmlspecialchars($download["file_path"]) . '?v=' . time();
        ?>
        <div class="dl-card reveal" data-name="<?php echo strtolower(htmlspecialchars($download['title'])); ?>" data-ext="<?php echo $ext; ?>" data-preview="<?php echo $previewUrl; ?>" data-title="<?php echo htmlspecialchars($download['title']); ?>" style="background:#fff;border-radius:8px;padding:20px;box-shadow:0 1px 4px rgba(0,0,0,.08);display:flex;flex-direction:column;gap:12px;">
          <div style="display:flex;align-items:flex-start;gap:14px;">
            <div style="width:44px;height:44px;background:#e8f0fe;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="bi <?php echo $icon; ?>" style="font-size:20px;color:#3a6fc4;"></i>
            </div>
            <div style="flex:1;min-width:0;">
              <div style="font-size:14px;font-weight:600;color:#1a1a2e;line-height:1.3;word-break:break-word;"><?php echo htmlspecialchars($download["title"]); ?></div>
              <div style="font-size:12px;color:#888;margin-top:4px;"><?php echo strtoupper($ext); ?> &bull; <?php echo $fileSize; ?></div>
            </div>
          </div>
          <button type="button" class="btn btn-preview js-preview"><i class="bi bi-eye" style="font-size:14px;"></i> Preview</button>
          <a href="<?php echo htmlspecialchars($download["file_path"]); ?>" download class="btn btn-download" data-download-id="<?php echo (int) $download["id"]; ?>"><i class="bi bi-download" style="font-size:14px;"></i> Download File</a>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div id="noResults" style="display:none;text-align:center;padding:60px 20px;color:#999;">
      <i class="bi bi-search" style="font-size:48px;display:block;margin-bottom:16px;color:#ccc;"></i>
      <p style="font-size:15px;">No documents match your search.</p>
    </div>
  </div>
</section>

<div id="previewModal" style="display:none;">
  <div class="preview-box">
    <div class="preview-header">
      <h3 id="previewTitle"><i class="bi bi-file-earmark"></i> <span>Document Preview</span></h3>
      <button class="preview-close" id="modalClose" aria-label="Close preview">&times;</button>
    </div>
    <div class="preview-body" id="previewBody"></div>
    <div class="preview-footer">
      <button class="btn btn-outline" style="font-size:12px;padding:8px 16px;" id="modalCloseBtn">Close</button>
      <a id="downloadBtn" href="#" download class="btn btn-download" style="font-size:12px;padding:8px 16px;text-decoration:none;"><i class="bi bi-download"></i> Download</a>
    </div>
  </div>
</div>

<?php include "include/footer.php"; ?>

<script>
(function(){
  'use strict';

  function filterDownloads(){
    var q = document.getElementById('downloadSearch').value.toLowerCase().trim();
    var cards = document.querySelectorAll('.dl-card');
    var grid = document.getElementById('downloadsGrid');
    var noResults = document.getElementById('noResults');
    var countEl = document.getElementById('downloadCount');
    var visible = 0;
    cards.forEach(function(card){
      var name = card.getAttribute('data-name') || '';
      if(!q || name.indexOf(q) !== -1){
        card.style.display = '';
        visible++;
      } else {
        card.style.display = 'none';
      }
    });
    if(countEl) countEl.textContent = visible + ' document' + (visible !== 1 ? 's' : '') + ' found';
    if(noResults) noResults.style.display = visible === 0 ? 'block' : 'none';
    if(grid) grid.style.display = visible === 0 ? 'none' : 'grid';
  }

  function openPreview(){
    var card = this.closest('.dl-card');
    if(!card) return;
    var title = card.getAttribute('data-title') || 'Document Preview';
    var previewUrl = card.getAttribute('data-preview') || '';
    var ext = (card.getAttribute('data-ext') || '').toLowerCase();
    var modal = document.getElementById('previewModal');
    var titleEl = document.getElementById('previewTitle');
    var body = document.getElementById('previewBody');
    var dlBtn = document.getElementById('downloadBtn');

    titleEl.innerHTML = '<i class="bi bi-file-earmark"></i> <span>' + title + '</span>';
    body.innerHTML = '';
    if(dlBtn) dlBtn.href = previewUrl;

    function setPlaceholder(icon, message){
      body.innerHTML = '<div class="placeholder"><i class="bi bi-' + icon + '"></i><p>' + message + '</p></div>';
    }

    if(!previewUrl){
      setPlaceholder('file-earmark', 'No file available for preview.');
    } else if(ext === 'pdf'){
      body.innerHTML = '<iframe src="' + previewUrl + '" allowfullscreen></iframe>';
    } else if(['jpg','jpeg','png','gif','webp','svg'].indexOf(ext) !== -1){
      body.innerHTML = '<img src="' + previewUrl + '" alt="' + title + '">';
    } else if(ext === 'txt'){
      body.innerHTML = '<pre id="txtPreview">Loading&hellip;</pre>';
      fetch(previewUrl).then(function(r){ return r.text(); }).then(function(text){
        var el = document.getElementById('txtPreview');
        if(el) el.textContent = text;
      }).catch(function(){
        setPlaceholder('file-text', 'Unable to load file content. Please try again or download the file.');
      });
    } else if(['doc','docx','xls','xlsx','ppt','pptx'].indexOf(ext) !== -1){
      var iconName = 'file-earmark';
      if(ext === 'doc' || ext === 'docx') iconName = 'file-word';
      else if(ext === 'xls' || ext === 'xlsx') iconName = 'file-excel';
      else if(ext === 'ppt' || ext === 'pptx') iconName = 'file-ppt';
      setPlaceholder(iconName, 'Previews are not supported for this file type. Please download it to view.');
    } else {
      setPlaceholder('file-earmark', 'Preview not available for this file type. Download to view.');
    }

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }

  function closePreview(){
    var modal = document.getElementById('previewModal');
    var body = document.getElementById('previewBody');
    modal.style.display = 'none';
    document.body.style.overflow = '';
    setTimeout(function(){ if(body) body.innerHTML = ''; }, 200);
  }

  function incrementDownload(id){
    if(!id) return;
    fetch('api/downloads.php?action=download&id=' + encodeURIComponent(id), {method:'POST'}).catch(function(){});
  }

  document.addEventListener('DOMContentLoaded', function(){
    var searchInput = document.getElementById('downloadSearch');
    if(searchInput) searchInput.addEventListener('input', filterDownloads);
    document.getElementById('downloadsGrid').addEventListener('click', function(e){
      var btn = e.target.closest('.js-preview');
      if(btn){ e.preventDefault(); openPreview.call(btn); }
    });

    document.getElementById('downloadsGrid').addEventListener('click', function(e){
      var dl = e.target.closest('a[data-download-id]');
      if(dl){
        incrementDownload(dl.getAttribute('data-download-id'));
      }
    });

    document.getElementById('modalClose').addEventListener('click', closePreview);
    document.getElementById('modalCloseBtn').addEventListener('click', closePreview);
    document.getElementById('previewModal').addEventListener('click', function(e){
      if(e.target === this) closePreview();
    });

    document.addEventListener('keydown', function(e){
      if(e.key === 'Escape') closePreview();
    });

    var cards = document.querySelectorAll('.dl-card');
    cards.forEach(function(el, i){
      el.style.opacity = '0';
      el.style.transform = 'translateY(40px)';
      el.style.transition = 'opacity .9s cubic-bezier(.16,1,.3,1), transform .9s cubic-bezier(.16,1,.3,1)';
      setTimeout(function(){ el.style.opacity = '1'; el.style.transform = 'translateY(0)'; }, (i % 3 + 1) * 150);
    });
  });
})();
</script>

</body>
</html>
