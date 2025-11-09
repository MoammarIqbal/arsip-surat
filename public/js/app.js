// ===== Token =====
const API_BASE = window.location.origin;
function getToken(){ return localStorage.getItem('token'); }
function setToken(t){ localStorage.setItem('token', t); }
function clearToken(){ localStorage.removeItem('token'); }

// ===== Loader & Topbar =====
const topbar = document.getElementById('topbar');
const loader = document.getElementById('app-loader');
let activeReq = 0;
function startLoading(){
  activeReq++;
  loader.style.display = 'flex';
  topbar.style.width = '30%';
  requestAnimationFrame(()=>{ topbar.style.width = '85%'; });
}
function stopLoading(){
  activeReq = Math.max(0, activeReq-1);
  if(activeReq===0){
    topbar.style.width = '100%';
    setTimeout(()=>{
      loader.style.display = 'none';
      topbar.style.width = '0';
    },150);
  }
}

// ===== Toasts =====
const toastWrap = document.getElementById('toast-wrap');
function showToast(type, title, message, timeout=3500){
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.innerHTML = `
    <div>
      <div class="title">${title || (type==='success'?'Berhasil':'Info')}</div>
      <div class="msg">${message || ''}</div>
    </div>
    <button class="close" aria-label="Tutup">
      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>`;
  toastWrap.appendChild(el);
  const remove=()=>{ el.style.opacity='0'; el.style.transform='translateY(4px)'; setTimeout(()=>el.remove(),200); }
  el.querySelector('.close').addEventListener('click', remove);
  setTimeout(remove, timeout);
}
const notify = {
  success:(msg, t)=>showToast('success','Berhasil',msg,t),
  error:(msg, t)=>showToast('error','Gagal',msg,t),
  info:(msg, t)=>showToast('info','Info',msg,t),
};

// ===== Confirm Modal =====
const confirmModal = document.getElementById('confirm-modal');
const confirmText  = document.getElementById('confirm-text');
const btnOk        = document.getElementById('confirm-ok');
const btnCancel    = document.getElementById('confirm-cancel');
function confirmDialog(text='Yakin?', okText='Ya', cancelText='Batal'){
  confirmText.textContent = text;
  btnOk.textContent = okText;
  btnCancel.textContent = cancelText;
  confirmModal.style.display = 'flex';
  return new Promise(resolve=>{
    const done=(v)=>{ confirmModal.style.display='none'; btnOk.onclick=null; btnCancel.onclick=null; resolve(v); };
    btnOk.onclick = ()=>done(true);
    btnCancel.onclick = ()=>done(false);
    confirmModal.querySelector('.bg').onclick = ()=>done(false);
  });
}

// ===== API Wrapper =====
async function apiFetch(path, options = {}) {
  const headers = options.headers || {};
  headers['Accept'] = 'application/json';
  if (!(options.body instanceof FormData)) {
    headers['Content-Type'] = headers['Content-Type'] || 'application/json';
  }
  const token = getToken();
  if (token) headers['Authorization'] = `Bearer ${token}`;
  startLoading();
  try {
    const res = await fetch(`${API_BASE}${path}`, { ...options, headers });
    const ct = res.headers.get('content-type') || '';
    const payload = ct.includes('application/json') ? await res.json().catch(()=>null) : await res.text();
    if (!res.ok) {
      const msg = (payload && payload.message) ? payload.message : `${res.status} ${res.statusText}`;
      notify.error(msg);
      throw new Error(msg);
    }
    return payload;
  } finally { stopLoading(); }
}

// ===== Active states nav (desktop & mobile) =====
function setActiveLinks(){
  const path = location.pathname;
  const wantsCreate = '/surat/tambah';
  const wantsList   = '/surat';
  document.querySelectorAll('.nav-link[data-path]').forEach(a=>{
    const want = a.getAttribute('data-path');
    let active = false;
    if (want === wantsCreate) active = path.startsWith(wantsCreate);
    else if (want === wantsList) active = path.startsWith(wantsList) && !path.startsWith(wantsCreate);
    else active = path.startsWith(want);
    if (active){ a.classList.add('active'); a.setAttribute('aria-current','page'); }
    else { a.classList.remove('active'); a.removeAttribute('aria-current'); }
  });
}

// ===== Mobile drawer toggler =====
function toggleMobileMenu(){
  const m = document.getElementById('mobile-menu');
  const expanded = m.classList.contains('hidden');
  m.classList.toggle('hidden', !expanded);
  document.getElementById('btn-menu').setAttribute('aria-expanded', expanded ? 'true' : 'false');
}

// ===== User dropdown (desktop) =====
(function bindUserDropdown(){
  const btn = document.getElementById('btn-user-menu');
  const dd  = document.getElementById('user-dropdown');
  if(!btn || !dd) return;
  let open = false;
  const close=()=>{ dd.classList.add('hidden'); dd.classList.remove('open'); open=false; }
  const openIt=()=>{ dd.classList.remove('hidden'); requestAnimationFrame(()=>dd.classList.add('open')); open=true; }
  btn.addEventListener('click', (e)=>{ e.stopPropagation(); open?close():openIt(); });
  document.addEventListener('click', (e)=>{ if(open && !dd.contains(e.target) && e.target!==btn) close(); });
  document.addEventListener('keydown',(e)=>{ if(open && e.key==='Escape') close(); });
})();

// ===== User Badge & Admin Menu =====
async function hydrateUserBadge() {
  const badge = document.getElementById('user-badge');
  const badgeMobile = document.getElementById('user-badge-mobile');
  const adminDesktop = document.getElementById('menu-admin-desktop');
  const adminMobile  = document.getElementById('menu-admin-mobile');

  const token = getToken();
  if (!token) {
    badge && (badge.textContent = 'Tidak login');
    badgeMobile && (badgeMobile.textContent = 'Tidak login');
    return;
  }

  try {
    const me = await apiFetch('/api/profile');
    badge && (badge.textContent = `${me.name} · ${me.role}`);
    badgeMobile && (badgeMobile.textContent = `${me.name} · ${me.role}`);

    if (me.role === 'admin') {
      adminDesktop && adminDesktop.classList.remove('hidden');
      adminMobile  && adminMobile.classList.remove('hidden');
    } else {
      adminDesktop && adminDesktop.classList.add('hidden');
      adminMobile  && adminMobile.classList.add('hidden');
    }
  } catch {
    badge && (badge.textContent = 'Token invalid');
    badgeMobile && (badgeMobile.textContent = 'Token invalid');
  }
}

// ===== Guard admin untuk halaman lain =====
window.ensureAdmin = async function(){
  const token = getToken();
  if (!token) { window.location.href = '/login'; throw new Error('not-logged-in'); }
  const me = await apiFetch('/api/profile');
  if (me.role !== 'admin') {
    notify.error('Akses ditolak: hanya admin.');
    window.location.href = '/surat';
    throw new Error('not-admin');
  }
  return me;
}

// ===== Init =====
document.addEventListener('DOMContentLoaded', () => {
  // Toggle mobile
  const btnMenu = document.getElementById('btn-menu');
  if (btnMenu) btnMenu.addEventListener('click', toggleMobileMenu);

  // Logout (desktop & mobile)
  const doLogout = async () => {
    const ok = await confirmDialog('Anda yakin ingin logout?','Logout','Batal');
    if(!ok) return;
    try { await apiFetch('/api/logout', { method: 'POST' }); } catch(e){}
    clearToken();
    notify.success('Berhasil logout');
    setTimeout(()=> window.location.href = '/', 450);
  };
  const btnLogout = document.getElementById('btn-logout');
  const btnLogoutMobile = document.getElementById('btn-logout-mobile');
  btnLogout && btnLogout.addEventListener('click', doLogout);
  btnLogoutMobile && btnLogoutMobile.addEventListener('click', doLogout);

  setActiveLinks();
  hydrateUserBadge();
});
