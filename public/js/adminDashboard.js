function openModal(id) 
{
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