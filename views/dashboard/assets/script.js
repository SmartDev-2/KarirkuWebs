// // Script minimal untuk interaksi sederhana
// document.addEventListener('DOMContentLoaded', function(){
// const search = document.getElementById('searchInput');
// if(search){
// search.addEventListener('keydown', function(e){
// if(e.key === 'Enter'){
// alert('Mencari: ' + search.value);
// }
// });
// }


// const notifBtn = document.getElementById('notifBtn');
// if(notifBtn){
// notifBtn.addEventListener('click', function(){
// alert('Tidak ada notifikasi baru');
// });
// }
// });

const ctx1 = document.getElementById('userChart').getContext('2d');
new Chart(ctx1, {
  type: 'line',
  data: {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
    datasets: [{
      label: 'Pengguna',
      data: [20, 50, 90, 130, 170, 230, 290],
      borderColor: '#0d6efd',
      fill: true,
      backgroundColor: 'rgba(13, 110, 253, 0.1)',
      tension: 0.3
    }]
  },
  options: { plugins: { legend: { display: false } } }
});

const ctx2 = document.getElementById('applyChart').getContext('2d');
new Chart(ctx2, {
  type: 'bar',
  data: {
    labels: ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'],
    datasets: [{
      label: 'Lamaran',
      data: [35, 50, 45, 65, 80, 45, 40],
      backgroundColor: '#0d6efd'
    }]
  },
  options: { plugins: { legend: { display: false } } }
});

