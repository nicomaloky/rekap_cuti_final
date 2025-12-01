// script.js

document.addEventListener('DOMContentLoaded', function() {
    // --- Logika Global: Notifikasi Sukses & Menu Mobile ---
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('status') === 'sukses' || urlParams.get('status_edit') === 'sukses' || urlParams.get('status_hapus') === 'sukses') {
        const pageTitle = document.querySelector('h1');
        if(pageTitle){
            const notification = document.createElement('div');
            notification.className = 'bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6';
            notification.textContent = 'Aksi berhasil dilakukan!';
            pageTitle.parentNode.insertBefore(notification, pageTitle);
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }
    }

    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    if (mobileMenuButton) {
        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }

    // --- Logika HANYA untuk Halaman Form Input Cuti ---
    const formCuti = document.getElementById('formCuti');
    if (formCuti) {
        const namaInput = document.getElementById('nama');
        const suggestionsBox = document.getElementById('nama-suggestions');
        const pegawaiIdInput = document.getElementById('pegawai_id');
        const jenisCutiSelect = document.getElementById('jenis_cuti');
        const sisaCutiInfo = document.getElementById('sisa-cuti-info');
        const sisaCutiValue = document.getElementById('sisa-cuti-value');
        const lamaCutiInput = document.getElementById('lama_cuti');
        const tglMulai = document.getElementById('tgl_mulai');
        const tglSelesai = document.getElementById('tgl_selesai');
        const cutiError = document.getElementById('cuti-error');
        let sisaCutiPegawai = 12;

        namaInput.addEventListener('keyup', async function() {
            const query = this.value;
            if (query.length < 2) { suggestionsBox.classList.add('hidden'); return; }
            try {
                const response = await fetch(`api.php?action=search_pegawai&q=${query}`);
                const pegawais = await response.json();
                suggestionsBox.innerHTML = '';
                if (pegawais.length > 0) {
                    pegawais.forEach(pegawai => {
                        const div = document.createElement('div');
                        let isPensiun = false;
                        let pensiunLabel = '';

                        if (pegawai.tmt_pensiun) {
                            const tmtDate = new Date(pegawai.tmt_pensiun);
                            const today = new Date();
                            today.setHours(0, 0, 0, 0);
                            if (tmtDate < today) {
                                isPensiun = true;
                                pensiunLabel = ' <span class="text-red-500 font-semibold">(Pensiun)</span>';
                            }
                        }

                        div.innerHTML = `${pegawai.nama} - ${pegawai.unit_kerja}${pensiunLabel}`;
                        
                        div.dataset.id = pegawai.id;
                        div.dataset.nama = pegawai.nama;
                        div.dataset.nip = pegawai.nip;
                        div.dataset.jabatan = pegawai.jabatan;
                        div.dataset.unit = pegawai.unit_kerja;
                        div.dataset.pensiun = isPensiun;

                        if (isPensiun) {
                            div.classList.add('text-gray-400', 'bg-gray-100', 'cursor-not-allowed');
                        }

                        suggestionsBox.appendChild(div);
                    });
                    suggestionsBox.classList.remove('hidden');
                }
            } catch (error) { console.error('Error fetching pegawai:', error); }
        });

        suggestionsBox.addEventListener('click', function(e) {
            const selectedDiv = e.target.closest('div');
            if (selectedDiv) {
                if (selectedDiv.dataset.pensiun === 'true') {
                    return;
                }
                
                namaInput.value = selectedDiv.dataset.nama;
                document.getElementById('nip').value = selectedDiv.dataset.nip;
                document.getElementById('jabatan').value = selectedDiv.dataset.jabatan;
                document.getElementById('unit_kerja').value = selectedDiv.dataset.unit;
                pegawaiIdInput.value = selectedDiv.dataset.id;
                suggestionsBox.classList.add('hidden');
                getSisaCuti(selectedDiv.dataset.id);
            }
        });

        async function getSisaCuti(pegawaiId) {
            try {
                const response = await fetch(`api.php?action=get_sisa_cuti&id=${pegawaiId}`);
                const data = await response.json();
                sisaCutiPegawai = data.sisa_cuti;
                sisaCutiValue.textContent = sisaCutiPegawai;
                if (jenisCutiSelect.value === '1') { // Cuti Tahunan ID = 1
                    sisaCutiInfo.classList.remove('hidden');
                }
                runAllValidations();
            } catch (error) { console.error('Error fetching sisa cuti:', error); }
        }
        
        function calculateWorkDays(startDate, endDate) {
            let count = 0;
            const currentDate = new Date(startDate.getTime());
            const lastDay = new Date(endDate.getTime());
            while (currentDate <= lastDay) {
                const dayOfWeek = currentDate.getDay();
                if (dayOfWeek !== 0 && dayOfWeek !== 6) { count++; }
                currentDate.setDate(currentDate.getDate() + 1);
            }
            return count;
        }

        function runAllValidations() {
            const startDateValue = tglMulai.value;
            const endDateValue = tglSelesai.value;
            let errorMessage = '';
            console.log(new Date(startDateValue));
            console.log(new Date(endDateValue));

            if (startDateValue && endDateValue) {
                const start = new Date(startDateValue);
                const end = new Date(endDateValue);

                if (end < start) {
                    errorMessage = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
                    lamaCutiInput.value = '';
                } else {
                    const selectedOption = jenisCutiSelect.options[jenisCutiSelect.selectedIndex];
                    const tipePerhitungan = selectedOption.dataset.tipe;
                    const maxDurasi = selectedOption.dataset.max ? parseInt(selectedOption.dataset.max, 10) : null;
                    
                    let durasi = 0;
                    if (tipePerhitungan === 'hari_kalender') {
                        const diffTime = Math.abs(end - start);
                        durasi = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                    } else {
                        durasi = calculateWorkDays(start, end);
                    }
                    lamaCutiInput.value = durasi;

                    if (durasi > 0) {
                        if (jenisCutiSelect.value === '1' && durasi > sisaCutiPegawai) {
                            errorMessage = `Pengajuan (${durasi} hari) melebihi sisa cuti tahunan (${sisaCutiPegawai} hari).`;
                        } else if (maxDurasi !== null && durasi > maxDurasi) {
                            errorMessage = `Durasi cuti tidak boleh melebihi ${maxDurasi} hari.`;
                        }
                    }
                }
            }
            cutiError.textContent = errorMessage;
            cutiError.classList.toggle('hidden', !errorMessage);
        }

        tglMulai.addEventListener('change', runAllValidations);
        tglSelesai.addEventListener('change', runAllValidations);
        jenisCutiSelect.addEventListener('change', () => {
            sisaCutiInfo.classList.toggle('hidden', !(jenisCutiSelect.value === '1' && pegawaiIdInput.value));
            runAllValidations();
        });
        formCuti.addEventListener('submit', function(e) {
             runAllValidations();
            if (!pegawaiIdInput.value) {
                alert('Harap pilih pegawai dari daftar hasil pencarian.');
                e.preventDefault();
            } else if (!cutiError.classList.contains('hidden')) {
                alert('Terdapat kesalahan pada data cuti. Harap perbaiki sebelum menyimpan.');
                e.preventDefault();
            }
        });
    }

    // --- Logika HANYA untuk Halaman Data Pegawai ---
    const dataPegawaiPage = document.getElementById('data-pegawai-page-container');
    if (dataPegawaiPage) {
        const btnShowAddForm = document.getElementById('btn-show-add-form');
        const addPegawaiModal = document.getElementById('add-pegawai-modal');
        const closeAddModalBtn = document.getElementById('close-add-modal-btn');
        const cancelAddBtn = document.getElementById('cancel-add-btn');
        const formGuruBaru = document.getElementById('formGuruBaru');
        
        const openAddModal = () => addPegawaiModal.classList.remove('hidden');
        const closeAddModal = () => {
            addPegawaiModal.classList.add('hidden');
            formGuruBaru.reset();
        };

        btnShowAddForm.addEventListener('click', openAddModal);
        closeAddModalBtn.addEventListener('click', closeAddModal);
        cancelAddBtn.addEventListener('click', closeAddModal);
        addPegawaiModal.addEventListener('click', (e) => { if (e.target === addPegawaiModal) closeAddModal(); });
        
        // --- LOGIKA MODAL HISTORY CUTI ---
        const historyModal = document.getElementById('history-modal');
        const historyTableBody = document.getElementById('history-table-body');
        const historyLoading = document.getElementById('history-loading');
        const historyEmpty = document.getElementById('history-empty');
        const historyNama = document.getElementById('history-nama-pegawai');
        
        // Fungsi helper untuk format tanggal
        function formatDate(dateString) {
            if(!dateString) return '-';
            const date = new Date(dateString);
            return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(date);
        }

        // Tutup modal history
        const closeHistoryModal = () => historyModal.classList.add('hidden');
        document.getElementById('close-history-modal-btn').addEventListener('click', closeHistoryModal);
        document.getElementById('close-history-btn-bottom').addEventListener('click', closeHistoryModal);
        historyModal.addEventListener('click', (e) => { if (e.target === historyModal) closeHistoryModal(); });

        // Event listener untuk tombol riwayat
        document.body.addEventListener('click', async function(event) {
            if (event.target.classList.contains('history-btn') || event.target.closest('.history-btn')) {
                const btn = event.target.classList.contains('history-btn') ? event.target : event.target.closest('.history-btn');
                const pegawaiId = btn.dataset.id;
                
                // Reset dan Tampilkan Modal
                historyModal.classList.remove('hidden');
                historyTableBody.innerHTML = '';
                historyLoading.classList.remove('hidden');
                historyEmpty.classList.add('hidden');
                historyNama.textContent = 'Memuat...';

                try {
                    const response = await fetch(`api.php?action=get_riwayat_cuti&id=${pegawaiId}`);
                    const result = await response.json();
                    
                    historyLoading.classList.add('hidden');
                    historyNama.textContent = result.nama;

                    if (result.data && result.data.length > 0) {
                        result.data.forEach(cuti => {
                            const tr = document.createElement('tr');
                            
                            // Badge Status
                            let statusBadge = '';
                            if (cuti.pertimbangan_atasan === 'Disetujui') statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Disetujui</span>';
                            else if (cuti.pertimbangan_atasan === 'Tidak Disetujui') statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Ditolak</span>';
                            else statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">' + (cuti.pertimbangan_atasan || 'Menunggu') + '</span>';

                            tr.innerHTML = `
                                <td class="px-4 py-2 whitespace-nowrap text-gray-900">${cuti.nama_cuti}</td>
                                <td class="px-4 py-2 whitespace-nowrap text-gray-500">${formatDate(cuti.tgl_mulai)} - ${formatDate(cuti.tgl_selesai)}</td>
                                <td class="px-4 py-2 whitespace-nowrap text-center text-gray-900">${cuti.lama_cuti} hari</td>
                                <td class="px-4 py-2 whitespace-nowrap text-gray-500 max-w-xs truncate" title="${cuti.alasan_cuti}">${cuti.alasan_cuti}</td>
                                <td class="px-4 py-2 whitespace-nowrap text-center">${statusBadge}</td>
                            `;
                            historyTableBody.appendChild(tr);
                        });
                    } else {
                        historyEmpty.classList.remove('hidden');
                    }
                } catch (error) {
                    console.error('Error fetching history:', error);
                    historyLoading.classList.add('hidden');
                    historyEmpty.textContent = 'Gagal memuat data.';
                    historyEmpty.classList.remove('hidden');
                }
            }
        });
    }

    // --- LOGIKA SEMUA POPUP (DIPERBARUI & DIGABUNG) ---
    const cutiModal = document.getElementById('edit-cuti-modal');
    const pegawaiModal = document.getElementById('edit-pegawai-modal');
    const deleteModal = document.getElementById('delete-confirm-modal');

    // Setup untuk popup edit cuti
    if (cutiModal) {
        const closeModalBtn = document.getElementById('close-modal-btn');
        const closeModal = () => cutiModal.classList.add('hidden');
        closeModalBtn.addEventListener('click', closeModal);
        cutiModal.addEventListener('click', (e) => { if (e.target === cutiModal) closeModal(); });
    }

    // Setup untuk popup edit pegawai
    if (pegawaiModal) {
        const closePegawaiModalBtn = document.getElementById('close-pegawai-modal-btn');
        const cancelPegawaiEditBtn = document.getElementById('cancel-pegawai-edit-btn');
        const closePegawaiModal = () => pegawaiModal.classList.add('hidden');
        closePegawaiModalBtn.addEventListener('click', closePegawaiModal);
        cancelPegawaiEditBtn.addEventListener('click', closePegawaiModal);
        pegawaiModal.addEventListener('click', (e) => { if (e.target === pegawaiModal) closePegawaiModal(); });
    }

    // Setup untuk popup konfirmasi hapus
    if (deleteModal) {
        const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
        const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
        const deleteConfirmText = document.getElementById('delete-confirm-text');
        let formToDelete = null;

        const openDeleteModal = () => deleteModal.classList.remove('hidden');
        const closeDeleteModal = () => {
            deleteModal.classList.add('hidden');
            formToDelete = null;
        };

        cancelDeleteBtn.addEventListener('click', closeDeleteModal);
        confirmDeleteBtn.addEventListener('click', () => {
            if (formToDelete) {
                formToDelete.submit();
            }
        });

        document.body.addEventListener('click', function(event) {
            if (event.target.classList.contains('delete-btn')) {
                const button = event.target;
                formToDelete = button.closest('form');
                const nama = button.dataset.nama;
                const type = button.dataset.type === 'cuti' ? 'data cuti ini' : `data pegawai "${nama}"`;
                deleteConfirmText.textContent = `Apakah Anda yakin ingin menghapus ${type}? Aksi ini tidak dapat dibatalkan.`;
                openDeleteModal();
            }
        });
    }

    // Event listener terpusat untuk semua tombol edit
    document.body.addEventListener('click', async function(event) {
        // Jika tombol edit cuti yang diklik
        if (event.target.classList.contains('edit-cuti-btn') || event.target.closest('.edit-cuti-btn')) {
            const btn = event.target.classList.contains('edit-cuti-btn') ? event.target : event.target.closest('.edit-cuti-btn');
            const cutiId = btn.dataset.id;
            try {
                const response = await fetch(`api.php?action=get_cuti_detail&id=${cutiId}`);
                const data = await response.json();
                if (data.error) { alert(data.error); return; }

                // Isi form popup cuti
                document.getElementById('edit-cuti-id').value = data.id;
                const jenisCutiSelect = document.getElementById('edit-jenis-cuti');
                jenisCutiSelect.innerHTML = '';
                const jenisOpsi = [ {id:1, nama:"Cuti Tahunan"}, {id:2, nama:"Cuti Besar"}, {id:3, nama:"Cuti Sakit"}, {id:4, nama:"Cuti Melahirkan"}, {id:5, nama:"Cuti Alasan Penting"}, {id:6, nama:"Cuti di Luar Tanggungan Negara"}];
                jenisOpsi.forEach(opsi => {
                    const option = document.createElement('option');
                    option.value = opsi.id; option.textContent = opsi.nama;
                    if (data.jenis_cuti_id == opsi.id) option.selected = true;
                    jenisCutiSelect.appendChild(option);
                });
                document.getElementById('edit-alasan-cuti').value = data.alasan_cuti;
                document.getElementById('edit-lama-cuti').value = data.lama_cuti;
                document.getElementById('edit-tgl-mulai').value = data.tgl_mulai;
                document.getElementById('edit-tgl-selesai').value = data.tgl_selesai;
                document.getElementById('edit-alamat-cuti').value = data.alamat_cuti;
                document.getElementById('edit-telp').value = data.telp;
                const statusSelect = document.getElementById('edit-status');
                statusSelect.innerHTML = '';
                const statusOpsi = ["Disetujui", "Perubahan", "Ditangguhkan", "Tidak Disetujui"];
                statusOpsi.forEach(opsi => {
                    const option = document.createElement('option');
                    option.value = opsi; option.textContent = opsi;
                    if (data.pertimbangan_atasan === opsi) option.selected = true;
                    statusSelect.appendChild(option);
                });
                cutiModal.classList.remove('hidden');
            } catch (error) {
                console.error('Gagal mengambil data cuti:', error);
                alert('Terjadi kesalahan saat mengambil data.');
            }
        }

        // Jika tombol edit pegawai yang diklik
        if (event.target.classList.contains('edit-pegawai-btn')) {
            const pegawaiId = event.target.dataset.id;
            try {
                const response = await fetch(`api.php?action=get_pegawai_detail&id=${pegawaiId}`);
                const data = await response.json();
                if (data.error) { alert(data.error); return; }

                // Isi form popup pegawai
                document.getElementById('edit-pegawai-id').value = data.id;
                document.getElementById('edit-nama').value = data.nama;
                document.getElementById('edit-nip').value = data.nip;
                document.getElementById('edit-tmt-pensiun').value = data.tmt_pensiun;
                const jabatanSelect = document.getElementById('edit-jabatan');
                jabatanSelect.innerHTML = '';
                const jabatanOpsi = ["Kepala Sekolah", "Guru", "Tata Usaha"];
                jabatanOpsi.forEach(opsi => {
                    const option = document.createElement('option');
                    option.value = opsi; option.textContent = opsi;
                    if (data.jabatan === opsi) option.selected = true;
                    jabatanSelect.appendChild(option);
                });
                const unitKerjaSelect = document.getElementById('edit-unit-kerja');
                unitKerjaSelect.innerHTML = '';
                for (let i = 1; i <= 23; i++) {
                    const namaSekolah = `SMPN ${i} Kota Bogor`;
                    const option = document.createElement('option');
                    option.value = namaSekolah; option.textContent = namaSekolah;
                    if (data.unit_kerja === namaSekolah) option.selected = true;
                    unitKerjaSelect.appendChild(option);
                }
                pegawaiModal.classList.remove('hidden');
            } catch (error) {
                console.error('Gagal mengambil data pegawai:', error);
                alert('Terjadi kesalahan saat mengambil data.');
            }
        }
    });

    // --- Logika HANYA untuk Halaman Dashboard ---
    const dashboardChart = document.getElementById('jenisCutiChart');
    if (dashboardChart) {
        const labels = JSON.parse(dashboardChart.dataset.labels);
        const values = JSON.parse(dashboardChart.dataset.values);

        new Chart(dashboardChart, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Pengajuan',
                    data: values,
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(255, 159, 64, 0.8)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    }
});