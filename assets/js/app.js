function openMenu(){document.getElementById('sideMenu')?.classList.add('show');document.getElementById('overlay')?.classList.add('show')}
function closeMenu(){document.getElementById('sideMenu')?.classList.remove('show');document.getElementById('overlay')?.classList.remove('show')}
function openAdminMenu(){document.getElementById('adminMenu')?.classList.add('show');document.getElementById('adminOverlay')?.classList.add('show')}
function closeAdminMenu(){document.getElementById('adminMenu')?.classList.remove('show');document.getElementById('adminOverlay')?.classList.remove('show')}
function showLogout(){document.getElementById('logoutModal')?.classList.add('show')}
function hideLogout(){document.getElementById('logoutModal')?.classList.remove('show')}
function markAllPresent(){document.querySelectorAll('.present-check').forEach(c=>c.checked=true)}
function generateId(){document.getElementById('orphanId').value='ORP'+Math.floor(100+Math.random()*900)}
