// Basic client JS: load packages, setup WhatsApp links, theme toggle, swiper
const config = { whatsappNumber: '919751396418' };

function waUrl() {
  const text = encodeURIComponent('Hi Misty Munnar Tours');
  return `https://wa.me/${config.whatsappNumber}?text=${text}`;
}

document.addEventListener('DOMContentLoaded', ()=>{
  // populate whatsapp links
  document.querySelectorAll('#whatsapp-link, #wa-float').forEach(a=>{a.setAttribute('href', waUrl()); a.setAttribute('target','_blank')});
  // load packages
  fetch('/api/packages.php').then(r=>r.json()).then(data=>{
    const list = document.getElementById('packages-list');
    const sel = document.getElementById('package-select');
    data.forEach(p=>{
      const card = document.createElement('div'); card.className='package glass';
      card.innerHTML = `<h4>${p.title}</h4><p>${p.short_desc}</p><p><strong>₹${p.price}</strong></p><a class='btn' href='#contact'>Enquire</a>`;
      list.appendChild(card);
      const option = document.createElement('option'); option.value = p.id; option.textContent = p.title; sel.appendChild(option);
    })
  }).catch(()=>{/* no packages yet */});

  // init swiper
  const swiper = new Swiper('.testimonials-swiper', {loop:true, pagination:{el:'.swiper-pagination'}});

  // theme toggle
  const toggle = document.getElementById('theme-toggle');
  toggle.addEventListener('click', ()=>{
    document.documentElement.classList.toggle('light');
    toggle.textContent = document.documentElement.classList.contains('light') ? '☀️' : '🌙';
  });

  // booking form submission via fetch to avoid navigation
  const form = document.getElementById('booking-form');
  form.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const fd = new FormData(form);
    const res = await fetch(form.action, {method:'POST',body:fd});
    const text = await res.text();
    alert(text);
    form.reset();
  });
});
