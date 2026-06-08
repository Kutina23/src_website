<form class="register-form" action="api/club-register.php" method="POST" enctype="multipart/form-data" id="clubRegistrationForm">
  <div class="form-row">
    <div class="form-field">
      <label for="clubName">Club Name *</label>
      <input type="text" id="clubName" name="club_name" required placeholder="e.g. Photography Club">
    </div>
    <div class="form-field">
      <label for="clubCategory">Club Category *</label>
      <select id="clubCategory" name="category" required>
        <option value="">Select Category</option>
        <option value="Technology">Technology</option>
        <option value="Sports">Sports</option>
        <option value="Arts & Culture">Arts & Culture</option>
        <option value="Academics">Academics</option>
        <option value="Music">Music</option>
        <option value="Media">Media</option>
        <option value="Governance">Governance</option>
        <option value="Sustainability">Sustainability</option>
        <option value="Other">Other</option>
      </select>
    </div>
  </div>
  
  <div class="form-row">
    <div class="form-field">
      <label for="presidentName">President Name *</label>
      <input type="text" id="presidentName" name="president_name" required placeholder="Full Name">
    </div>
    <div class="form-field">
      <label for="presidentStudentId">President Student ID *</label>
      <input type="text" id="presidentStudentId" name="president_student_id" required placeholder="e.g. HLTU/23/XXXX">
    </div>
  </div>
  
  <div class="form-row">
    <div class="form-field">
      <label for="contactEmail">Contact Email *</label>
      <input type="email" id="contactEmail" name="contact_email" required placeholder="email@hltu.edu.gh">
    </div>
    <div class="form-field">
      <label for="contactPhone">Contact Phone</label>
      <input type="tel" id="contactPhone" name="contact_phone" placeholder="+233 XX XXX XXXX">
    </div>
  </div>
  
  <div class="form-field">
    <label for="clubDescription">Club Description *</label>
    <textarea id="clubDescription" name="description" required placeholder="Brief description of club purpose and activities..." rows="4"></textarea>
  </div>
  
  <div class="form-field">
    <label for="initialMembers">Initial Members (Min. 10) *</label>
    <input type="number" id="initialMembers" name="initial_members" required min="10" placeholder="Number of founding members">
  </div>
  
  <div class="form-field">
    <label for="clubLogo">Club Logo / Avatar</label>
    <div class="file-upload">
      <input type="file" accept="image/*" id="clubLogo" name="club_logo">
      <label for="clubLogo" class="file-label">
        <i class="bi bi-upload"></i>
        <span>Click to upload club logo</span>
      </label>
    </div>
  </div>
  
  <button type="submit" style="padding: 14px 32px; font-size: 13px; font-weight: 600; letter-spacing: 0.12em; text-transform: uppercase; color: var(--navy); background: linear-gradient(135deg, var(--gold-light), var(--gold)); border: none; cursor: pointer;">Submit Registration →</button>
</form>

<style>
  #clubRegSuccess {
    display: none;
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    border-radius: 8px;
    padding: 16px 20px;
    margin-bottom: 24px;
    font-weight: 600;
    font-size: 15px;
    text-align: center;
  }
</style>

<script>
  (function () {
    const form = document.getElementById('clubRegistrationForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const btn = form.querySelector('[type="submit"]');
      btn.disabled = true;
      btn.textContent = 'Submitting…';

      const formData = new FormData(form);
      fetch(form.action, {
        method: 'POST',
        body: formData
      })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data.success) {
          // Remove any old banner
          var old = document.getElementById('clubRegSuccess');
          if (old) old.parentNode.removeChild(old);

          // Prepend success banner
          var notice = document.createElement('div');
          notice.id = 'clubRegSuccess';
          notice.textContent = data.message || 'Registration submitted successfully!';
          form.parentNode.insertBefore(notice, form);

          form.reset();
        } else {
          alert(data.message || 'Registration failed. Please try again.');
        }
      })
      .catch(function () {
        alert('An error occurred. Please try again.');
      })
      .finally(function () {
        btn.disabled = false;
        btn.textContent = 'Submit Registration →';
      });
    });
  })();
</script>