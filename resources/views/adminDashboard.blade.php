<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/adminDashboard.css') }}?v={{ time() }}">
</head>

<body>

<div class="mainBody">
<div class="container-fluid vh-100 p-2">
    <div class="row h-100 g-2">
        <!-- SIDEBAR -->
        <div class="col-lg-2 col-md-3 sidebar text-center p-3">
            <div class="profile mb-4">
                <i class="bi bi-person-circle"></i>
                <h6>{{ Auth::user()->name ?? 'Staff' }}</h6>
                <small>
                    @if(Auth::user()->usertype === 'admin')
                        ADMINISTRATOR
                    @else
                        USER
                    @endif
                </small>
            </div>

            <div class="mt-auto">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-warning w-100" style="font-weight:600; color:#fff;">
                        <i class="bi bi-box-arrow-right me-2"></i>LOG OUT
                    </button>
                </form>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="col-lg-10 col-md-9 main-panel p-3">

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        {{ $error }}<br>
                    @endforeach
                </div>
            @endif

            <div class="dashboard">

                <div class="box-btn" onclick="openModal('createModal')">
                    <i class="fas fa-user-plus"></i>
                    <span>Create Account</span>
                </div>

                <div class="box-btn" onclick="openModal('videoModal')">
                    <i class="fas fa-video"></i>
                    <span>Videos</span>
                </div>

                <div class="box-btn" onclick="openModal('usersModal')">
                    <i class="fas fa-users"></i>
                    <span>Manage Users</span>
                </div>

                <div class="box-btn" onclick="openModal('deleteModal')">
                    <i class="fas fa-trash"></i>
                    <span>Delete Queue</span>
                </div>

            </div>

            <!-- Create Account Modal -->
            <div id="createModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('createModal')">&times;</span>
                    <h3>Create Account</h3>
                    <p>Fill in the details to create a new staff account.</p>
                    <form action="/admin/create-account" method="POST">
                        @csrf

                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Enter full name" required>
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" placeholder="Enter email address" required>
                        </div>

                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required>
                        </div>

                        <div class="form-group">
                            <label>User Type</label>
                            <select name="usertype" class="form-control" required>
                                <option value="">-- Select User Type --</option>
                                <option value="Regular">Regular</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Date Verified</label>
                            <input type="date" name="email_verified_at" class="form-control">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mt-3">Create Account</button>
                    </form>
                </div>
            </div>

            <!-- Videos Modal -->
            <div id="videoModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('videoModal')">&times;</span>
                    <h3>Manage Videos</h3>
                    <p>Upload videos to display on the TV screen.</p>

                    <form action="/admin/videos/store" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label>Upload Video File</label>
                            <input type="file" name="video_url" class="form-control" accept="video/mp4,video/webm,video/ogg" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Upload Video</button>
                    </form>

                    <hr>

                    <div class="table-responsive">
                        <h5><i class="fas fa-list me-2"></i>Video Playlist</h5>
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Video File</th>
                                    <th>Status</th>
                                    <th>Show</th>
                                    <th>Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($videos as $index => $video)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            @if(file_exists(public_path('vid/' . $video->video_url)))
                                                <span title="{{ $video->video_url }}">
                                                    {{ Str::limit($video->video_url, 30) }}
                                                </span>
                                            @else
                                                <span class="text-danger">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    {{ Str::limit($video->video_url, 20) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge-{{ $video->is_active ? 'active' : 'inactive' }}">
                                                {{ $video->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if(file_exists(public_path('vid/' . $video->video_url)))
                                                <form action="/admin/videos/toggle/{{ $video->id }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="checkbox" name="is_active" value="1"
                                                        onchange="this.form.submit()"
                                                        {{ $video->is_active ? 'checked' : '' }}>
                                                </form>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <form action="/admin/videos/{{ $video->id }}" method="POST"
                                                onsubmit="return confirm('Delete this video permanently?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Users Modal -->
            <div id="usersModal" class="modal">
                <div class="modal-content" style="width: 700px; max-width: 95%;">
                    <span class="close" onclick="closeModal('usersModal')">&times;</span>
                    <h3>Manage Users</h3>
                    <p>View and manage all registered accounts.</p>
                    <div class="table-responsive" style="padding: 0 24px 24px;">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <th>Created</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $index => $user)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <strong>{{ $user->name }}</strong>
                                            @if($user->id === Auth::id())
                                                <span class="badge bg-secondary ms-1" style="font-size:0.65rem;">YOU</span>
                                            @endif
                                        </td>
                                        <td>{{ $user->email }}</td>
                                        <td>
                                            <span class="badge-{{ $user->usertype === 'admin' ? 'admin' : 'regular' }}">
                                                {{ ucfirst($user->usertype) }}
                                            </span>
                                        </td>
                                        <td>{{ $user->created_at->format('Y-m-d') }}</td>
                                        <td>
                                            @if($user->id !== Auth::id())
                                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                                    onsubmit="return confirm('Delete user {{ $user->name }} permanently?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-danger" title="Delete user">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Delete Queue Modal -->
            <div id="deleteModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('deleteModal')">&times;</span>
                    <h3>Delete Queue Numbers</h3>
                    <p>This will permanently clear all queue records.</p>
                    <div style="padding: 0 24px 24px;">
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            This action cannot be undone.
                        </div>
                        <button class="btn btn-danger w-100" onclick="confirmDelete()">
                            <i class="fas fa-trash me-2"></i>Delete All Records
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
    <script>
        function openModal(id) {
            document.getElementById(id).style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
            document.body.style.overflow = '';
        }

        function confirmDelete() {
            if (confirm('Are you sure you want to clear all queue numbers?')) {
                window.location.href = "{{ route('admin.clear.queue') }}";
            }
        }

        // Close modal on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                    document.body.style.overflow = '';
                }
            });
        });
    </script>
</body>
</html>
