<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="{{ asset('css/adminDashboard.css') }}?v={{ time() }}">
</head>

<body>

<div class="mainBody">
<div class="container-fluid vh-100 p-2">
    <div class="row h-100 g-2">
        <!-- SIDEBAR -->
        <div class="col-lg-2 col-md-3 sidebar text-center p-3" style="background-color: rgba(255, 255, 255, 0.2); color: #ffffff;">
            <div class="profile mb-3">
                <i class="bi bi-person-circle fs-1"></i>
                <h6 class="mt-2">{{ Auth::user()->name ?? 'Staff' }}</h6>
                <small>USER</small>
            </div>

           <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-warning w-100 mt-auto" style="color: #ffffff; font-weight:bold;">
                    LOG OUT
                </button>
            </form>
        </div>

        <!-- MAIN CONTENT -->
        <div class="col-lg-10 col-md-9 main-panel p-3">
            <div class="dashboard">

                <div class="box-btn" onclick="openModal('createModal')">
                    <i class="fas fa-user-plus"></i>
                    <span>Create Account</span>
                </div>

                <div class="box-btn" onclick="openModal('videoModal')">
                    <i class="fas fa-video"></i>
                    <span>Videos</span>
                </div>

                <div class="box-btn" onclick="openModal('deleteModal')">
                    <i class="fas fa-trash"></i>
                    <span>Delete Queue Numbers</span>
                </div>

            </div>

            <!-- Create Account Modal -->
            <div id="createModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('createModal')">&times;</span>
                    <h3>Create Account</h3>
                    <p>User Details.</p>
                    <form action="/admin/create-account" method="POST">
                            @csrf

                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>User Type</label>
                                <select name="usertype" class="form-control" required>
                                    <option value="">-- Select User Type --</option>
                                    <option value="regular">Regular</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Date Verified</label>
                                <input type="date" name="email_verified_at" class="form-control">
                            </div>

                            <br>
                            <button type="submit" class="btn btn-primary w-100 mt-auto">Create Account</button>
                        </form>
                </div>
            </div>

            <!-- Videos Modal -->
            <div id="videoModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('videoModal')">&times;</span>
                    <h3>Manage Videos</h3>
                    <p>Upload or manage TV videos here.</p>
                        <form action="/admin/videos/store" method="POST">
                            @csrf
                            <div class="form-group">
                                <label>Video File Name</label>
                                <input type="text" name="video_url" class="form-control" placeholder="Paste video file name here" required>
                            </div>
                            <br>
                            <button class="btn btn-primary w-100 mt-auto">Add Video</button>
                        </form>

                        <hr>

                        <!-- Playlist -->
                       <h5>Video List</h5>
                       <div class="table-responsive">
                            <table class="table table-bordered table-striped">
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
                                                    {{ $video->video_url }}
                                                @else
                                                    <span class="text-danger">
                                                        {{ $video->video_url }} - Video file not found
                                                    </span>
                                                @endif
                                            </td>

                                            <td>{{ $video->is_active ? 'Active' : 'Inactive' }}</td>

                                            <td>
                                                @if(file_exists(public_path('vid/' . $video->video_url)))
                                                    <form action="/admin/videos/toggle/{{ $video->id }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="checkbox"
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
                                                <button class="btn btn-sm btn-danger" title="Delete">
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

            <!-- Delete Queue Modal -->
            <div id="deleteModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('deleteModal')">&times;</span>
                    <h3>Delete Queue Numbers</h3>
                    <p>This will clear all queue records.</p>
                    <button class="btn btn-primary" onclick="confirmDelete()">Delete Now</button>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
    <script>
        function openModal(id) {
            document.getElementById(id).style.display = 'block';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        function confirmDelete() {
            if (confirm('Are you sure you want to clear all queue numbers?')) {
                window.location.href = "{{ route('admin.clear.queue') }}";
            }
        }
    </script>
</body>
</html>





