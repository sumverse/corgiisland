/* ==========================================
   웰시코기톡 - 통합 JavaScript 파일
   ========================================== */

document.addEventListener("DOMContentLoaded", function () {
  console.log("웰시코기톡 로드 완료!");

  initCommon();
  initHamburgerMenu();

  if (document.querySelector(".gallery-grid")) {
    initGallery();
  }

  if (document.querySelector(".board-list")) {
    initBoard();
  }

  // 테스트 페이지는 JS 기능 사용 안 함
});

// ==========================================
// 햄버거 메뉴 기능
// ==========================================
function initHamburgerMenu() {
  const hamburgerMenu = document.getElementById("hamburgerMenu");
  const mobileMenu = document.getElementById("mobileMenu");
  const mobileMenuOverlay = document.getElementById("mobileMenuOverlay");
  const closeMenu = document.getElementById("closeMenu");

  if (!hamburgerMenu || !mobileMenu) return;

  // 햄버거 버튼 클릭
  hamburgerMenu.addEventListener("click", function () {
    mobileMenu.classList.add("active");
    mobileMenuOverlay.classList.add("active");
    document.body.style.overflow = "hidden";
  });

  // 닫기 버튼 클릭
  if (closeMenu) {
    closeMenu.addEventListener("click", closeMobileMenu);
  }

  // 오버레이 클릭
  if (mobileMenuOverlay) {
    mobileMenuOverlay.addEventListener("click", closeMobileMenu);
  }

  function closeMobileMenu() {
    mobileMenu.classList.remove("active");
    mobileMenuOverlay.classList.remove("active");
    document.body.style.overflow = "";
  }
}

// ==========================================
// 공통 기능
// ==========================================
function initCommon() {
  // 알림 메시지 자동 숨김 (3초 후)
  const alerts = document.querySelectorAll(".alert");
  alerts.forEach((alert) => {
    setTimeout(() => {
      alert.style.transition = "opacity 0.5s";
      alert.style.opacity = "0";
      setTimeout(() => alert.remove(), 500);
    }, 3000);
  });

  // 폼 제출 시 로딩 표시 - 갤러리와 게시판만!
  const uploadForms = document.querySelectorAll(".upload-form");
  const writeForms = document.querySelectorAll(".write-form");

  uploadForms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn && !submitBtn.disabled) {
        submitBtn.disabled = true;
        submitBtn.textContent = "업로드 중...";
      }
    });
  });

  writeForms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn && !submitBtn.disabled) {
        submitBtn.disabled = true;
        submitBtn.textContent = "등록 중...";
      }
    });
  });
}

// ==========================================
// 갤러리 기능
// ==========================================
function initGallery() {
  const galleryImages = document.querySelectorAll(".gallery-image img");
  galleryImages.forEach((img) => {
    img.addEventListener("error", function () {
      this.parentElement.innerHTML = "이미지를 불러올 수 없습니다";
    });
  });

  const photoInput = document.querySelector('input[name="photo"]');
  if (photoInput) {
    photoInput.addEventListener("change", function (e) {
      const file = e.target.files[0];
      if (file) {
        if (file.size > 5242880) {
          alert("파일 크기는 5MB 이하여야 합니다.");
          this.value = "";
          return;
        }

        const validTypes = [
          "image/jpeg",
          "image/jpg",
          "image/png",
          "image/gif",
        ];
        if (!validTypes.includes(file.type)) {
          alert("jpg, png, gif 파일만 업로드 가능합니다.");
          this.value = "";
          return;
        }
      }
    });
  }

  const likeButtons = document.querySelectorAll(".like-button");
  likeButtons.forEach((btn) => {
    btn.addEventListener("click", function (e) {
      this.style.transform = "scale(1.2)";
      setTimeout(() => {
        this.style.transform = "scale(1)";
      }, 200);
    });
  });
}

// ==========================================
// 게시판 기능
// ==========================================
function initBoard() {
  const boardItems = document.querySelectorAll(".board-item");
  boardItems.forEach((item) => {
    item.addEventListener("click", function () {
      this.style.transform = "scale(0.98)";
      setTimeout(() => {
        this.style.transform = "scale(1)";
      }, 100);
    });
  });
}

// ==========================================
// 유틸리티 함수
// ==========================================
function formatDate(dateString) {
  const date = new Date(dateString);
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

function timeAgo(dateString) {
  const now = new Date();
  const past = new Date(dateString);
  const diffMs = now - past;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);

  if (diffMins < 1) return "방금 전";
  if (diffMins < 60) return `${diffMins}분 전`;
  if (diffHours < 24) return `${diffHours}시간 전`;
  if (diffDays < 7) return `${diffDays}일 전`;

  return formatDate(dateString);
}

function confirmAction(message) {
  return confirm(message);
}

function showToast(message, type = "info", duration = 3000) {
  const existingToast = document.querySelector(".toast");
  if (existingToast) {
    existingToast.remove();
  }

  const toast = document.createElement("div");
  toast.className = `toast toast-${type}`;
  toast.textContent = message;
  toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${
              type === "success"
                ? "#4caf50"
                : type === "error"
                ? "#f44336"
                : "#2196f3"
            };
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            animation: slideIn 0.3s ease-out;
        `;

  document.body.appendChild(toast);

  setTimeout(() => {
    toast.style.animation = "slideOut 0.3s ease-out";
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

const style = document.createElement("style");
style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
document.head.appendChild(style);
