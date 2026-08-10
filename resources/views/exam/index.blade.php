<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Mata Pelajaran</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">

    <div class="max-w-3xl mx-auto bg-white p-6 rounded-lg shadow-md">
        
        @if(session('success'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Selesai!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <h1 class="text-2xl font-bold mb-6 text-center text-blue-600">Daftar Mata Pelajaran</h1>

        <div class="space-y-4">
            @foreach($subjects as $subject)
                <div class="border p-4 rounded-md flex justify-between items-center">
                    <div>
                        <h2 class="text-lg font-semibold">{{ $subject->name }}</h2>
                        <p class="text-gray-500 text-sm">{{ $subject->description }}</p>
                    </div>
                    <a href="{{ route('exam.show', $subject->id) }}" 
                       class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Buka Mata Pelajaran
                    </a>
                </div>
            @endforeach
        </div>
    </div>

</body>
</html>