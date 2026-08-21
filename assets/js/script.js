// Enhanced client JS: WhatsApp booking template, per-package WA buttons, booking form WA
const config = { whatsappNumber: '919751396418' };

function waUrl(message){
  const text = encodeURIComponent(message);
  return `https://wa.me/${config.whatsappNumber}?text=${text}`;
}

function buildPrefillMessage({name, phone, travel_date, people, package_name}){
  // Default template, omit empty parts
  const parts = [];
  parts.push('Hi Misty Munnar Tours,');
  if(package_name) parts.push(`I would like to book the ${package_name} package`);
  if(travel_date) parts.push(`on ${travel_date}`);
  if(people) parts.push(`for ${people} people`);
  let body = parts.join(' ');
  const contactParts = [];
  if(name) contactParts.push(`My name is ${name}`);
  if(phone) contactParts.push(`my phone number is ${phone}`);
  if(contactParts.length) body = `${body}. ${contactParts.join(' and ')}.`;
  // If only header then add a short greeting
  if(body.trim() === 'Hi Misty Munnar Tours,') body = 'Hi Misty Munnar Tours';
  return body;
}

document.addEventListener('DOMContentLoaded', ()=>{
  // populate whatsapp links
  document.querySelectorAll('#whatsapp-link, #wa-float').forEach(a=>{a.setAttribute('href', waUrl('Hi Misty Munnar Tours')); a.setAttribute('target','_blank')});

  // load packages
  fetch('/api/packages.php').then(r=>r.json()).then(data=>{
    const list = document.getElementById('packages-list');
    const sel = document.getElementById('package-select');
    data.forEach(p=>{
      const card = document.createElement('div'); card.className='package glass';
      card.innerHTML = `<h4>${escapeHtml(p.title)}</h4><p>${escapeHtml(p.short_desc)}</p><p><strong>₹${escapeHtml(p.price)}</strong></p>
        <div style="display:flex;gap:0.5rem">
          <a class='btn' href='#contact'>Enquire</a>
          <button class='btn btn-alt btn-wa' data-id='${p.id}' data-title='${escapeAttr(p.title)}'>Book on WhatsApp</button>
        </div>`;
      list.appendChild(card);
      const option = document.createElement('option'); option.value = p.id; option.textContent = p.title; sel.appendChild(option);
    })

    // attach click handlers to newly created WA buttons
    document.querySelectorAll('.btn-wa').forEach(btn=>{
      btn.addEventListener('click', (e)=>{
        const title = btn.dataset.title || '';
        const message = buildPrefillMessage({package_name: title});
        window.open(waUrl(message), '_blank');
      });
    });

  }).catch(()=>{/* no packages yet */});

  // init swiper
  if(window.Swiper) new Swiper('.testimonials-swiper', {loop:true, pagination:{el:'.swiper-pagination'}});

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

  // booking form: WhatsApp button
  const waBtn = document.getElementById('booking-wa-btn');
  waBtn.addEventListener('click', ()=>{
    const name = document.getElementById('bf-name').value.trim();
    const phone = document.getElementById('bf-phone').value.trim();
    const travel_date = document.getElementById('bf-date').value;
    const people = document.getElementById('bf-people').value;
    const packageSel = document.getElementById('package-select');
    const package_name = packageSel.options[packageSel.selectedIndex]?.text || '';
    const message = buildPrefillMessage({name, phone, travel_date, people, package_name});
    window.open(waUrl(message), '_blank');
  });

});

// small helpers to avoid XSS when injecting data
function escapeHtml(str){ if(str==null) return ''; return String(str).replace(/[&<>"']/g, function(m){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]; }); }
function escapeAttr(str){ return escapeHtml(str).replace(/\n/g,' '); }
