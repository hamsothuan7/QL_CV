document.addEventListener('DOMContentLoaded', function() {
  // MENU LOGIC
  var sidebar = document.getElementById('sidebar');
  var sidebarToggle = document.getElementById('sidebarToggle');
  var sidebarOverlay = document.getElementById('sidebarOverlay');
  var sidebarClose = document.getElementById('sidebarClose');
  let lastScrollTop = 0;
  let ticking = false;
  function checkWidth() {
    if(window.innerWidth <= 768) {
      sidebar.classList.remove('active');
      sidebarOverlay.classList.remove('active');
      sidebar.style.display = 'none';
      sidebarOverlay.style.display = 'none';
      sidebarToggle.style.display = 'block';
      sidebarClose.style.display = 'none';
      sidebarClose.style.setProperty('display', 'none', 'important');
    } else {
      sidebar.classList.remove('active');
      sidebarOverlay.classList.remove('active');
      sidebar.style.display = 'block';
      sidebarOverlay.style.display = 'none';
      sidebarToggle.style.display = 'none';
      sidebarClose.style.display = 'none';
      sidebarClose.style.setProperty('display', 'none', 'important');
    }
  }
  checkWidth();
  window.addEventListener('resize', checkWidth);
  sidebarToggle.addEventListener('click', function() {
    sidebar.classList.add('active');
    sidebarOverlay.classList.add('active');
    sidebar.style.display = 'block';
    sidebarOverlay.style.display = 'block';
    sidebarToggle.style.display = 'none';
    if(window.innerWidth <= 768) {
      sidebarClose.style.display = 'block';
    }
  });
  sidebarOverlay.addEventListener('click', function() {
    sidebar.classList.remove('active');
    sidebarOverlay.classList.remove('active');
    sidebar.style.display = 'none';
    sidebarOverlay.style.display = 'none';
    sidebarToggle.style.display = 'block';
    sidebarClose.style.display = 'none';
    sidebarClose.style.setProperty('display', 'none', 'important');
  });
  sidebarClose.addEventListener('click', function() {
    sidebar.classList.remove('active');
    sidebarOverlay.classList.remove('active');
    sidebar.style.display = 'none';
    sidebarOverlay.style.display = 'none';
    sidebarToggle.style.display = 'block';
    sidebarClose.style.display = 'none';
    sidebarClose.style.setProperty('display', 'none', 'important');
  });

  // FILTER LOGIC
  
}); 

// Hiệu ứng ẩn/hiện nút hamburger khi cuộn trang
let ticking = false;
let lastScrollTop = 0;

window.addEventListener('scroll', function() {
  if (!sidebarToggle) return;
  if (!ticking) {
    window.requestAnimationFrame(function() {
      let st = window.pageYOffset || document.documentElement.scrollTop;
      if (st > lastScrollTop) {
        // Cuộn xuống: ẩn dần
        sidebarToggle.style.transition = 'opacity 0.3s, transform 0.3s';
        sidebarToggle.style.opacity = '0';
        sidebarToggle.style.transform = 'translateY(-30px)';
      } else {
        // Cuộn lên: hiện lại
        sidebarToggle.style.transition = 'opacity 0.3s, transform 0.3s';
        sidebarToggle.style.opacity = '1';
        sidebarToggle.style.transform = 'translateY(0)';
      }
      lastScrollTop = st <= 0 ? 0 : st;
      ticking = false;
    });
    ticking = true;
  }
}); 
