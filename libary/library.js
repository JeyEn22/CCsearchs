// ===== BOOK GRID SCROLL =====
const bookGrid = document.querySelector('.book-grid');
let position = 0;
const cardWidth = 195; // width + gap

document.getElementById('nextBtn').addEventListener('click', () => {
  const maxScroll = -(bookGrid.scrollWidth - bookGrid.parentElement.offsetWidth);
  position -= cardWidth * 2; // move 2 cards per click
  if (position < maxScroll) position = maxScroll;
  bookGrid.style.transform = `translateX(${position}px)`;
});

document.getElementById('prevBtn').addEventListener('click', () => {
  position += cardWidth * 2;
  if (position > 0) position = 0;
  bookGrid.style.transform = `translateX(${position}px)`;
});

// ===== BANNER ANIMATIONS =====
window.addEventListener('DOMContentLoaded', () => {
  const headerBox = document.querySelector('.header-box');
  const bannerLeft = document.querySelector('.banner-left');
  const bannerCenter = document.querySelector('.banner-center');
  const bannerRight = document.querySelector('.banner-right');

  // Fade in the banner container
  headerBox.classList.add('fade-in');

  // Animate children with staggered delay
  setTimeout(() => bannerLeft.classList.add('slide-left'), 300);
  setTimeout(() => bannerCenter.classList.add('slide-up'), 500);
  setTimeout(() => bannerRight.classList.add('slide-right'), 700);
});
