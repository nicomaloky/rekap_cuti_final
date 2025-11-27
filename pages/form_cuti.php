<?php require_once __DIR__ . '/../database.php'; ?>
<div class="bg-white p-8 rounded-lg shadow-lg w-full">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Formulir Input Cuti</h1>
    <!-- UPDATE: Ditambahkan enctype="multipart/form-data" -->
    <form id="formCuti" action="proses_cuti.php" method="POST" enctype="multipart/form-data">
        <fieldset class="border p-4 rounded-md shadow-lg mb-6">
            <legend class="text-lg font-semibold text-gray-700 px-2">1. Data Pegawai</legend>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                <div class="relative">
                    <label for="nama" class="block text-sm font-medium text-gray-700">Nama Pegawai</label>
                    <input type="text" id="nama" name="nama" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Ketik nama untuk mencari..." autocomplete="off" required>
                    <div id="nama-suggestions" class="hidden"></div>
                    <input type="hidden" id="pegawai_id" name="pegawai_id">
                </div>
                <div>
                    <label for="nip" class="block text-sm font-medium text-gray-700">NIP</label>
                    <input type="text" id="nip" name="nip" class="mt-1 block w-full bg-gray-200 border-gray-300 rounded-md shadow-sm" readonly>
                </div>
                <div>
                    <label for="jabatan" class="block text-sm font-medium text-gray-700">Jabatan</label>
                    <input type="text" id="jabatan" name="jabatan" class="mt-1 block w-full bg-gray-200 border-gray-300 rounded-md shadow-sm" readonly>
                </div>
                <div>
                    <label for="unit_kerja" class="block text-sm font-medium text-gray-700">Unit Kerja</label>
                    <input type="text" id="unit_kerja" name="unit_kerja" class="mt-1 block w-full bg-gray-200 border-gray-300 rounded-md shadow-sm" readonly>
                </div>
            </div>
        </fieldset>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8">
            <div>
                <fieldset class="border p-4 rounded-md shadow-lg mb-6">
                    <legend class="text-lg font-semibold text-gray-700 px-2">2. Jenis Cuti</legend>
                    <div class="mt-4">
                        <select id="jenis_cuti" name="jenis_cuti_id" class="block w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="">-- Pilih Jenis Cuti --</option>
                            <?php
                            $sql_jenis_cuti = "SELECT id, nama_cuti, max_durasi, tipe_perhitungan FROM jenis_cuti ORDER BY id";
                            $result_jenis_cuti = $conn->query($sql_jenis_cuti);
                            while($jenis = $result_jenis_cuti->fetch_assoc()) {
                                echo "<option 
                                        value='" . $jenis['id'] . "' 
                                        data-tipe='" . $jenis['tipe_perhitungan'] . "' 
                                        data-max='" . $jenis['max_durasi'] . "'>" 
                                        . htmlspecialchars($jenis['nama_cuti']) . 
                                      "</option>";
                            }
                            ?>
                        </select>
                        <div id="sisa-cuti-info" class="hidden mt-3 p-3 bg-blue-100 border-blue-300 text-blue-800 text-sm rounded-md">
                            Sisa Cuti Tahunan: <span id="sisa-cuti-value" class="font-bold"></span> hari.
                        </div>
                    </div>
                </fieldset>
                
                <!-- UPDATE: Fieldset Alasan & Bukti Cuti -->
                <fieldset class="border p-4 rounded-md shadow-lg mb-6">
                    <legend class="text-lg font-semibold text-gray-700 px-2">3. Alasan & Bukti Cuti</legend>
                    <div class="mt-4">
                        <label for="alasan_cuti" class="block text-sm font-medium text-gray-700 mb-1">Alasan Cuti</label>
                        <textarea id="alasan_cuti" name="alasan_cuti" rows="4" class="block w-full border-gray-300 rounded-md shadow-sm" placeholder="Tuliskan alasan lengkap..." required></textarea>
                    </div>
                    <!-- Input File Baru -->
                    <div class="mt-4">
                        <label for="bukti_cuti" class="block text-sm font-medium text-gray-700 mb-1">Upload Dokumen Pendukung (Opsional)</label>
                        <p class="text-xs text-gray-500 mb-2">Format: PDF/JPG/PNG. Maks 2MB.</p>
                        <input type="file" id="bukti_cuti" name="bukti_cuti" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm text-slate-500
                          file:mr-4 file:py-2 file:px-4
                          file:rounded-full file:border-0
                          file:text-sm file:font-semibold
                          file:bg-indigo-50 file:text-indigo-700
                          hover:file:bg-indigo-100
                        "/>
                    </div>
                </fieldset>
            </div>
            <div>
                <fieldset class="border p-4 rounded-md shadow-lg mb-6">
                    <legend class="text-lg font-semibold text-gray-700 px-2">4. Durasi Cuti</legend>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mt-4">
                        <div class="sm:col-span-1">
                            <label for="lama_cuti" class="block text-sm font-medium text-gray-700">Lama (Hari)</label>
                            <input type="number" id="lama_cuti" name="lama_cuti" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Otomatis" required>
                        </div>
                        <div class="sm:col-span-1">
                            <label for="tgl_mulai" class="block text-sm font-medium text-gray-700">Mulai</label>
                            <input type="date" id="tgl_mulai" name="tgl_mulai" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>
                        <div class="sm:col-span-1">
                            <label for="tgl_selesai" class="block text-sm font-medium text-gray-700">Selesai</label>
                            <input type="date" id="tgl_selesai" name="tgl_selesai" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>
                    </div>
                    <p id="cuti-error" class="text-red-500 text-sm mt-2 hidden"></p>
                </fieldset>
                <fieldset class="border p-4 rounded-md shadow-lg mb-6">
                    <legend class="text-lg font-semibold text-gray-700 px-2">5. Alamat & Telepon</legend>
                    <div class="grid grid-cols-1 gap-6 mt-4">
                        <div>
                            <label for="alamat_cuti" class="block text-sm font-medium text-gray-700">Alamat</label>
                            <input type="text" id="alamat_cuti" name="alamat_cuti" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Alamat lengkap" required>
                        </div>
                        <div>
                            <label for="telp" class="block text-sm font-medium text-gray-700">No. Telepon</label>
                            <input type="tel" id="telp" name="telp" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Nomor yang bisa dihubungi" required>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>
        <fieldset class="border p-4 rounded-md shadow-lg">
            <legend class="text-lg font-semibold text-gray-700 px-2">6. Status Persetujuan Atasan</legend>
            <div class="mt-4 space-y-2">
                <div class="flex items-center"><input type="radio" name="pertimbangan_atasan" value="Disetujui" class="h-4 w-4 text-indigo-600 border-gray-300" required><label class="ml-3 block text-sm font-medium text-gray-700">Disetujui</label></div>
                <div class="flex items-center"><input type="radio" name="pertimbangan_atasan" value="Perubahan" class="h-4 w-4 text-indigo-600 border-gray-300"><label class="ml-3 block text-sm font-medium text-gray-700">Perubahan</label></div>
                <div class="flex items-center"><input type="radio" name="pertimbangan_atasan" value="Ditangguhkan" class="h-4 w-4 text-indigo-600 border-gray-300"><label class="ml-3 block text-sm font-medium text-gray-700">Ditangguhkan</label></div>
                <div class="flex items-center"><input type="radio" name="pertimbangan_atasan" value="Tidak Disetujui" class="h-4 w-4 text-indigo-600 border-gray-300"><label class="ml-3 block text-sm font-medium text-gray-700">Tidak Disetujui</label></div>
            </div>
        </fieldset>
        <div class="flex justify-end mt-6">
            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Simpan Data Cuti</button>
        </div>
    </form>
</div>