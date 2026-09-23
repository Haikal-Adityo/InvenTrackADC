@extends('layouts.app')

@php
    $actor = auth()->user();
    $isTeknikActor = $actor->isTeknik();
    $isTeknikManager = $actor->isManager() && $isTeknikActor;
    $canManageUsers = $actor->isSuperAdmin() || $actor->isAdmin() || $isTeknikManager;
    $isApprovalManager = $actor->isManager() && !$isTeknikManager;
@endphp

@section('title', $actor->isSuperAdmin() ? 'User Management Global' : ($isApprovalManager ? 'Approval User' : 'Manajemen User'))
@section('subtitle', $actor->isSuperAdmin() ? 'Kelola akun pengguna Bidang Umum dan Teknik' : ($isApprovalManager ? 'Setujui atau tolak akun pengguna' : 'Kelola akun pengguna sistem'))

@section('content')
    <div class="animate-fade-in">
        <!-- Filter Bar -->
        <!-- Header Actions Wrapper -->
        <div class="header-action-wrapper d-none">
            <div class="section-header-actions">
                <form method="GET" action="{{ $isApprovalManager ? route('pendingUsers.index') : route('users.index') }}">
                    <div class="action-row-1 usr-filter-row">
                        <div class="usr-search-row d-flex align-items-center gap-2" style="flex-wrap: nowrap !important;">
                            <div class="position-relative" id="userSearchWrapper" style="flex: 1 1 auto !important; width: auto !important; min-width: 0 !important;">
                                <input type="text"
                                    id="userSearchInput"
                                    class="form-control form-control-sm"
                                    name="search"
                                    value="{{ request('search') }}"
                                    autocomplete="off"
                                    placeholder="Cari user...">
                                <div id="userSearchSuggestions" class="autocomplete-suggestions" style="display:none;"></div>
                            </div>
                            <a href="{{ $isApprovalManager ? route('pendingUsers.index') : route('users.index') }}" 
                                class="btn btn-reset btn-outline-reset" 
                                style="flex: 0 0 auto !important;"
                                title="Reset Filter">
                                <i class="bi bi-arrow-counterclockwise fs-5"></i>
                            </a>
                        </div>
                        
                        <div class="usr-select-row">
                            <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Semua Role</option>
                                @if(auth()->user()->isSuperAdmin())
                                    <option value="superadmin" {{ request('role') == 'superadmin' ? 'selected' : '' }}>Superadmin</option>
                                @endif
                                <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                                <option value="manajer" {{ request('role') == 'manajer' ? 'selected' : '' }}>Manajer</option>
                                @if(!$isTeknikActor)
                                    <option value="staf" {{ request('role') == 'staf' ? 'selected' : '' }}>Staf</option>
                                @endif
                            </select>
                            @if(auth()->user()->isSuperAdmin())
                                <select name="bidang" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">Semua Bidang</option>
                                    <option value="umum" {{ request('bidang') == 'umum' ? 'selected' : '' }}>Umum</option>
                                    <option value="teknik" {{ request('bidang') == 'teknik' ? 'selected' : '' }}>Teknik</option>
                                </select>
                            @endif
                            @if($canManageUsers)
                                <select name="account_status" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">Semua Status</option>
                                    <option value="pending" {{ request('account_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approved" {{ request('account_status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="rejected" {{ request('account_status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                            @endif
                        </div>
                        
                        @if($canManageUsers)
                            <div class="usr-action-row">
                                <button type="button" class="btn btn-primary btn-sm w-100" onclick="openUserModal()">
                                    <i class="bi bi-person-plus-fill me-1"></i> Tambah User
                                </button>
                            </div>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table" id="users-table">
                        <thead>
                            <tr>
                                <th style="width:50px;">No</th>
                                <th>Nama</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Bidang</th>
                                <th>No HP</th>
                                <th>Persetujuan</th>
                                @if($canManageUsers)
                                <th>Status</th>
                                @endif
                                <th>Terdaftar</th>
                                @if($canManageUsers)
                                <th>Terakhir Login</th>
                                @endif
                                <th style="width:140px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $index => $user)
                                <tr data-user-id="{{ $user->id }}"
                                     data-name="{{ $user->name }}"
                                     data-username="{{ $user->username }}"
                                     data-email="{{ $user->email }}"
                                     data-role="{{ $user->role }}"
                                     data-bidang="{{ $user->bidang }}">
                                    <td>{{ $users->firstItem() + $index }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div
                                                style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-light));display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:11px;flex-shrink:0;">
                                                @php
                                                    $words = explode(' ', $user->name);
                                                    $initials = strtoupper(substr($words[0], 0, 1));
                                                    
                                                    if (count($words) > 1) {
                                                        $initials .= strtoupper(substr(end($words), 0, 1));
                                                    }
                                                @endphp
                                                {{ $initials }}
                                            </div>
                                            <span class="fw-600">{{ $user->name }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $user->username }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @php
                                            $roleColors = ['superadmin' => 'badge-role-superadmin', 'admin' => 'badge-role-admin', 'manajer' => 'badge-role-manajer', 'staf' => 'badge-role-staf'];
                                        @endphp
                                        <span class="badge {{ $roleColors[$user->role] ?? 'bg-secondary' }}"
                                            style="font-size:11px; padding:14px 37px !important; border-radius:20px;">
                                            {{ ucfirst($user->role) }}
                                        </span>
                                    </td>
                                    <td>{{ $user->bidang ? ucfirst($user->bidang) : 'Global' }}</td>
                                    <td>{{ $user->no_hp ?? '-' }}</td>
                                    <td>
                                        <span
                                            class="badge-status badge-{{ $user->account_status }}">{{ ucfirst($user->account_status) }}</span>
                                    </td>
                                    @if($canManageUsers)
                                        @php $isOnline = $onlineUserIds->has($user->id); @endphp
                                        <td>
                                            <span class="badge-status {{ $isOnline ? 'badge-online' : 'badge-offline' }}">
                                                <i class="bi bi-circle-fill" style="font-size:7px;"></i>
                                                {{ $isOnline ? 'Online' : 'Offline' }}
                                            </span>
                                        </td>
                                    @endif
                                    <td style="font-size:12px; color:var(--text-secondary);">
                                        {{ $user->created_at->format('d/m/Y') }}
                                    </td>
                                    @if($canManageUsers)
                                    <td style="font-size:12px;">
                                        {{ $user->last_login_at ? \Carbon\Carbon::parse($user->last_login_at)->format('d/m/Y, H:i') : '-' }}
                                    </td>
                                    @endif
                                    <td>
                                        <div class="action-buttons">
                                            {{-- Manager: Approve/Reject user account --}}
                                            @if($isApprovalManager && $user->account_status === 'pending')
                                                <form action="{{ route('users.approveAccount', $user->id) }}" method="POST"
                                                    style="display:inline;" id="approveUser-{{ $user->id }}">
                                                    @csrf
                                                    <button type="button" class="btn-action approve" title="Approve"
                                                        onclick="swalConfirm('Approve User', 'Approve akun user ini?', 'question', 'Ya, Approve', '#approveUser-{{ $user->id }}')">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('users.rejectAccount', $user->id) }}" method="POST"
                                                    style="display:inline;" id="rejectUser-{{ $user->id }}">
                                                    @csrf
                                                    <button type="button" class="btn-action reject" title="Reject"
                                                        onclick="swalConfirm('Reject User', 'Reject akun user ini?', 'warning', 'Ya, Reject', '#rejectUser-{{ $user->id }}')">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- Admin: Edit/Delete --}}
                                            @if($canManageUsers)
                                                <button type="button" class="btn-action edit" title="Edit"
                                                    onclick="openUserModal({{ $user->id }})">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </button>
                                                @if($user->id !== auth()->id())
                                                    <form action="{{ route('users.destroy', $user) }}" method="POST"
                                                        id="deleteUser-{{ $user->id }}">
                                                        @csrf @method('DELETE')
                                                        <button type="button" class="btn-action delete" title="Hapus"
                                                            onclick="swalConfirm('Hapus User', 'Yakin hapus user ini? Data yang sudah dihapus tidak bisa dikembalikan.', 'warning', 'Ya, Hapus', '#deleteUser-{{ $user->id }}')">
                                                            <i class="bi bi-trash-fill"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                @if($actor->isSuperAdmin())
                                                    <form action="{{ route('users.resetPassword', $user) }}" method="POST"
                                                        id="resetPasswordUser-{{ $user->id }}">
                                                        @csrf
                                                        <button type="button" class="btn-action reset-password" title="Reset Password"
                                                            onclick="openResetPasswordModal('{{ $user->id }}', {{ Illuminate\Support\Js::from($user->name) }})">
                                                            <i class="bi bi-key-fill"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr class="no-data-row">
                                    <td colspan="{{ $canManageUsers ? 12 : 10 }}">
                                        <i class="bi bi-people"
                                            style="font-size:40px;display:block;margin-bottom:8px;opacity:0.3;"></i>
                                        {{ $isApprovalManager ? 'Tidak ada user menunggu approval' : 'Belum ada data user' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($users->hasPages())
            <div class="d-flex justify-content-center mt-3">
                {{ $users->links('pagination.custom') }}
            </div>
        @endif
    </div>

    {{-- User Modal (Create/Edit) --}}
    @if($canManageUsers)
        <div class="modal fade inventrack-modal" id="userModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="position:relative;">
                    <div class="modal-loading-overlay" id="userLoading">
                        <div class="modal-spinner"></div>
                    </div>
                    <div class="modal-header">
                        <h5 class="modal-title" id="userModalTitle">
                            <i class="bi bi-person-plus-fill"></i> <span>Tambah User</span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="modal-error-alert" id="userError">
                            <i class="bi bi-exclamation-circle me-1"></i>
                            <span id="userErrorMsg"></span>
                        </div>
                        <form id="userForm" novalidate>
                            <input type="hidden" id="userId" value="">
                            <input type="hidden" id="userMethod" value="POST">

                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Masukkan nama lengkap" required
                                    id="userName">
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Username <span class="text-danger">*</span></label>
                                    <input type="text" name="username" class="form-control" placeholder="username.login" required
                                        id="userUsername" autocomplete="username">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" placeholder="nama@email.com" required
                                        id="userEmail">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">No HP</label>
                                    <input type="text" name="no_hp" class="form-control" placeholder="08xxxxxxxxxx"
                                        id="userNoHp">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-select" required id="userRole">
                                    <option value="">-- Pilih Role --</option>
                                    @if(auth()->user()->isSuperAdmin())
                                        <option value="superadmin">Superadmin</option>
                                    @endif
                                    <option value="admin">Admin</option>
                                    <option value="manajer">Manajer</option>
                                    @if(!$isTeknikActor || auth()->user()->isSuperAdmin())
                                        <option value="staf" data-role-option="staf">Staf</option>
                                    @endif
                                </select>
                            </div>

                            @if(auth()->user()->isSuperAdmin())
                                <div class="mb-3">
                                    <label class="form-label">Bidang <span class="text-danger">*</span></label>
                                    <select name="bidang" class="form-select" id="userBidang">
                                        <option value="">-- Pilih Bidang --</option>
                                        <option value="umum">Umum</option>
                                        <option value="teknik">Teknik</option>
                                    </select>
                                    <small class="text-muted">Tidak wajib untuk role Superadmin.</small>
                                </div>
                            @endif

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" id="userPasswordLabel">Password <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group password-field-group">
                                        <input type="password" name="password" class="form-control"
                                            placeholder="Default: {{ $defaultPassword }}" id="userPassword"
                                            autocomplete="new-password">
                                        <button class="input-group-text password-toggle-btn" type="button"
                                            data-target="userPassword" aria-label="Lihat password" title="Lihat password">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" id="userPasswordConfirmLabel">Konfirmasi Password <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group password-field-group">
                                        <input type="password" name="password_confirmation" class="form-control"
                                            placeholder="Ulangi default password" id="userPasswordConfirm"
                                            autocomplete="new-password">
                                        <button class="input-group-text password-toggle-btn" type="button"
                                            data-target="userPasswordConfirm" aria-label="Lihat konfirmasi password"
                                            title="Lihat password">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="default-password-note">
                                        <i class="bi bi-key-fill"></i>
                                        <label for="userDefaultPassword" class="mb-0">Default password</label>
                                        <input type="text" id="userDefaultPassword" class="default-password-readonly"
                                            value="{{ $defaultPassword }}" readonly tabindex="-1" aria-readonly="true">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary" id="userSubmitBtn" onclick="submitUserForm()">
                            <i class="bi bi-check-lg"></i> Simpan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Reset Password Confirmation Modal --}}
    @if($actor->isSuperAdmin())
        <div class="modal fade inventrack-modal" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="position:relative;">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-key-fill"></i> <span>Reset Password User</span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">
                            Yakin ingin mereset password user <strong id="resetPasswordUserName">-</strong> ke password
                            default? Password lama user ini tidak akan berlaku lagi setelah direset.
                        </p>
                        <div class="default-password-note">
                            <i class="bi bi-key-fill"></i>
                            <label for="resetPasswordDefaultValue" class="mb-0">Password default</label>
                            <input type="text" id="resetPasswordDefaultValue" class="default-password-readonly"
                                value="{{ $defaultPassword }}" readonly tabindex="-1" aria-readonly="true">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-danger" id="resetPasswordConfirmBtn">
                            <i class="bi bi-check-lg"></i> Ya, Reset Password
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@if($canManageUsers)
    @push('scripts')
        <script>
            const userModalEl = document.getElementById('userModal');
            document.body.appendChild(userModalEl);
            const userModal = new bootstrap.Modal(userModalEl);
            const isTeknikActorUser = @json($isTeknikActor);
            const defaultUserPassword = @json($defaultPassword);

            function updateUserRoleOptions() {
                const roleSelect = document.getElementById('userRole');
                const bidangSelect = document.getElementById('userBidang');
                const staffOption = roleSelect ? roleSelect.querySelector('option[value="staf"]') : null;
                const selectedBidang = bidangSelect ? bidangSelect.value : (isTeknikActorUser ? 'teknik' : 'umum');
                const hideStaff = selectedBidang === 'teknik';

                if (staffOption) {
                    staffOption.hidden = hideStaff;
                    staffOption.disabled = hideStaff;
                    if (hideStaff && roleSelect.value === 'staf') {
                        roleSelect.value = '';
                    }
                }
            }

            if (document.getElementById('userBidang')) {
                document.getElementById('userBidang').addEventListener('change', updateUserRoleOptions);
            }

            function setUserPasswordFields(password = defaultUserPassword) {
                document.getElementById('userPassword').type = 'password';
                document.getElementById('userPasswordConfirm').type = 'password';
                document.getElementById('userPassword').value = password;
                document.getElementById('userPasswordConfirm').value = password;
                document.getElementById('userDefaultPassword').value = defaultUserPassword;
                document.querySelectorAll('.password-toggle-btn').forEach(button => {
                    button.setAttribute('aria-label', 'Lihat password');
                    button.setAttribute('title', 'Lihat password');
                    button.querySelector('i').className = 'bi bi-eye';
                });
            }

            document.querySelectorAll('.password-toggle-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const input = document.getElementById(this.dataset.target);
                    const showPassword = input.type === 'password';
                    input.type = showPassword ? 'text' : 'password';
                    this.setAttribute('aria-label', showPassword ? 'Sembunyikan password' : 'Lihat password');
                    this.setAttribute('title', showPassword ? 'Sembunyikan password' : 'Lihat password');
                    this.querySelector('i').className = showPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
                });
            });

            function openUserModal(id = null) {
                // Reset
                document.getElementById('userForm').reset();
                document.getElementById('userError').style.display = 'none';
                document.querySelectorAll('#userForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
                setUserPasswordFields();
                updateUserRoleOptions();

                if (id) {
                    // Edit mode
                    document.getElementById('userModalTitle').innerHTML = '<i class="bi bi-pencil-fill"></i> <span>Edit User</span>';
                    document.getElementById('userId').value = id;
                    document.getElementById('userMethod').value = 'PUT';
                    document.getElementById('userSubmitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Update';
                    document.getElementById('userPasswordLabel').innerHTML = 'Password Baru';
                    document.getElementById('userPasswordConfirmLabel').innerHTML = 'Konfirmasi Password Baru';
                    document.getElementById('userPassword').placeholder = 'Kosongkan jika tidak ingin mengubah password';
                    document.getElementById('userPasswordConfirm').placeholder = 'Ulangi password baru';
                    document.getElementById('userPassword').removeAttribute('required');
                    document.getElementById('userPasswordConfirm').removeAttribute('required');
                    document.getElementById('userPassword').value = '';
                    document.getElementById('userPasswordConfirm').value = '';

                    const loading = document.getElementById('userLoading');
                    loading.classList.add('show');
                    userModal.show();

                    fetch(`{{ request()->getBaseUrl() }}/users/${id}/edit-data`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(res => res.json())
                        .then(data => {
                            loading.classList.remove('show');
                            document.getElementById('userName').value = data.name;
                            document.getElementById('userUsername').value = data.username;
                            document.getElementById('userEmail').value = data.email;
                            document.getElementById('userNoHp').value = data.no_hp || '';
                            document.getElementById('userRole').value = data.role;
                            if (document.getElementById('userBidang')) {
                                document.getElementById('userBidang').value = data.bidang || '';
                            }
                            document.getElementById('userDefaultPassword').value = defaultUserPassword;
                            updateUserRoleOptions();
                        })
                        .catch(() => {
                            loading.classList.remove('show');
                            document.getElementById('userErrorMsg').textContent = 'Gagal memuat data user.';
                            document.getElementById('userError').style.display = 'block';
                        });
                } else {
                    // Create mode
                    document.getElementById('userModalTitle').innerHTML = '<i class="bi bi-person-plus-fill"></i> <span>Tambah User</span>';
                    document.getElementById('userId').value = '';
                    document.getElementById('userMethod').value = 'POST';
                    document.getElementById('userSubmitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Simpan';
                    document.getElementById('userPasswordLabel').innerHTML = 'Password <span class="text-danger">*</span>';
                    document.getElementById('userPasswordConfirmLabel').innerHTML = 'Konfirmasi Password <span class="text-danger">*</span>';
                    document.getElementById('userPassword').placeholder = `Default: ${defaultUserPassword}`;
                    document.getElementById('userPasswordConfirm').placeholder = 'Ulangi default password';
                    document.getElementById('userPassword').setAttribute('required', 'required');
                    document.getElementById('userPasswordConfirm').setAttribute('required', 'required');
                    setUserPasswordFields();
                    updateUserRoleOptions();
                    userModal.show();
                }
            }

            function submitUserForm() {
                const form = document.getElementById('userForm');
                const errorDiv = document.getElementById('userError');
                const errorMsg = document.getElementById('userErrorMsg');
                const loading = document.getElementById('userLoading');
                const submitBtn = document.getElementById('userSubmitBtn');
                const userId = document.getElementById('userId').value;
                const method = document.getElementById('userMethod').value;

                errorDiv.style.display = 'none';
                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

                loading.classList.add('show');
                submitBtn.disabled = true;

                const formData = new FormData(form);
                const url = userId ? `{{ request()->getBaseUrl() }}/users/${userId}` : `{{ request()->getBaseUrl() }}/users`;

                if (method === 'PUT') {
                    formData.append('_method', 'PUT');
                }

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                    .then(res => res.json().then(data => ({ status: res.status, data })))
                    .then(({ status, data }) => {
                        loading.classList.remove('show');
                        submitBtn.disabled = false;

                        if (data.success) {
                            userModal.hide();
                            Toast.fire({ icon: 'success', title: data.message });
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            let messages = [];
                            if (data.errors) {
                                Object.keys(data.errors).forEach(key => {
                                    messages.push(data.errors[key][0]);
                                    const input = form.querySelector(`[name="${key}"]`);
                                    if (input) input.classList.add('is-invalid');
                                });
                            }
                            if (data.message && !data.errors) messages.push(data.message);
                            errorMsg.innerHTML = messages.join('<br>');
                            errorDiv.style.display = 'block';
                        }
                    })
                    .catch(() => {
                        loading.classList.remove('show');
                        submitBtn.disabled = false;
                        errorMsg.textContent = 'Terjadi kesalahan. Silakan coba lagi.';
                        errorDiv.style.display = 'block';
                    });
            }

            // Reset on close
            userModalEl.addEventListener('hidden.bs.modal', function () {
                document.getElementById('userForm').reset();
                document.getElementById('userError').style.display = 'none';
                document.querySelectorAll('#userForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
            });

            // Reset Password confirmation modal (superadmin only)
            const resetPasswordModalEl = document.getElementById('resetPasswordModal');
            let resetPasswordModal = null;
            let resetPasswordFormId = null;

            if (resetPasswordModalEl) {
                document.body.appendChild(resetPasswordModalEl);
                resetPasswordModal = new bootstrap.Modal(resetPasswordModalEl);

                document.getElementById('resetPasswordConfirmBtn').addEventListener('click', function () {
                    if (resetPasswordFormId) {
                        document.getElementById(resetPasswordFormId).submit();
                    }
                });
            }

            function openResetPasswordModal(id, name) {
                if (!resetPasswordModal) return;
                resetPasswordFormId = 'resetPasswordUser-' + id;
                document.getElementById('resetPasswordUserName').textContent = name;
                resetPasswordModal.show();
            }

            // Autocomplete search suggestions for Users
            (function() {
                const searchInput = document.getElementById('userSearchInput');
                const suggestionsBox = document.getElementById('userSearchSuggestions');
                const searchWrapper = document.getElementById('userSearchWrapper');
                const autocompleteUrl = @json(route('api.autocomplete.users'));

                if (!searchInput || !suggestionsBox || !searchWrapper) return;

                let debounceTimer = null;

                searchInput.addEventListener('input', function() {
                    const keyword = this.value.trim();
                    if (!keyword) {
                        suggestionsBox.style.display = 'none';
                        suggestionsBox.innerHTML = '';
                        return;
                    }

                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        const params = new URLSearchParams({ q: keyword });

                        fetch(`${autocompleteUrl}?${params.toString()}`, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(res => res.json())
                        .then(results => renderSuggestions(results))
                        .catch(() => { suggestionsBox.style.display = 'none'; });
                    }, 200);
                });

                function renderSuggestions(items) {
                    if (!items.length) {
                        suggestionsBox.innerHTML = '<div class="autocomplete-no-result">Tidak ada user ditemukan</div>';
                        suggestionsBox.style.display = 'block';
                        return;
                    }

                    suggestionsBox.innerHTML = items.map(item => `
                        <div class="autocomplete-item" data-name="${item.name}">
                            <div class="autocomplete-icon">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <div class="autocomplete-content">
                                <div class="autocomplete-title">${item.name}</div>
                                <div class="autocomplete-subtitle">
                                    @${item.username} • ${item.role} • ${item.bidang || 'umum'}
                                </div>
                            </div>
                        </div>
                    `).join('');

                    suggestionsBox.style.display = 'block';

                    suggestionsBox.querySelectorAll('.autocomplete-item').forEach(el => {
                        el.addEventListener('click', function() {
                            searchInput.value = this.dataset.name;
                            suggestionsBox.style.display = 'none';
                            searchInput.closest('form').submit();
                        });
                    });
                }

                document.addEventListener('click', function(e) {
                    if (searchWrapper && !searchWrapper.contains(e.target)) {
                        suggestionsBox.style.display = 'none';
                    }
                });

                searchInput.addEventListener('focus', function() {
                    if (this.value.trim() !== '' && suggestionsBox.innerHTML.trim() !== '') {
                        suggestionsBox.style.display = 'block';
                    }
                });
            })();
        </script>
    @endpush
@endif
