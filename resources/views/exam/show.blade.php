<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mata Pelajaran: {{ $subject->name }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">

    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <div class="border-b pb-4 mb-6 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-800">Mata Pelajaran: {{ $subject->name }}</h1>
            <a href="{{ route('exam.index') }}" class="text-blue-500 hover:underline">Kembali</a>
        </div>

        <form action="{{ route('exam.submit', $subject->id) }}" method="POST">
            @csrf
            
            @foreach($questions as $index => $question)
                <div class="mb-8 p-4 bg-gray-50 rounded-lg border">
                    <div class="font-semibold text-lg mb-4 flex items-start">
                        <span class="mr-2">{{ $questions->firstItem() + $index }}.</span>
                        <span>{{ $question->payload }}</span>
                    </div>

                    <div class="space-y-3 pl-2">
                        @foreach($question->answers as $answer)
                            @php 
                                $huruf = chr(97 + $loop->index); 
                                $isChecked = (isset($savedAnswers[$question->id]) && $savedAnswers[$question->id] == $answer->id) ? 'checked' : '';
                            @endphp
                            
                            <label class="flex items-center space-x-4 cursor-pointer p-2 hover:bg-gray-200 rounded-lg transition-colors">
                                <input type="radio" 
                                       name="answers[{{ $question->id }}]" 
                                       value="{{ $answer->id }}" 
                                       class="hidden peer"
                                       onchange="simpanJawaban('{{ $subject->id }}', '{{ $question->id }}', '{{ $answer->id }}')"
                                       {{ $isChecked }}>
                                
                                <span class="w-8 h-8 flex items-center justify-center rounded-full border-2 border-gray-400 text-gray-600 font-bold uppercase transition-colors peer-checked:bg-blue-500 peer-checked:border-blue-500 peer-checked:text-white">
                                    {{ $huruf }}
                                </span>
                                <span class="text-gray-700 peer-checked:text-blue-700 peer-checked:font-semibold transition-colors">
                                    {{ $answer->text }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="mt-6 mb-6">
                {{ $questions->links() }}
            </div>

            <div class="text-right border-t pt-4">
                <button type="submit" onclick="return confirm('Yakin ingin mengumpulkan jawaban?')" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-6 rounded">
                    Kumpulkan Jawaban
                </button>
            </div>
        </form>
    </div>

    <script>
        function simpanJawaban(subjectId, questionId, answerId) {
            fetch(`/mata-pelajaran/${subjectId}/simpan-jawaban`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    question_id: questionId,
                    answer_id: answerId
                })
            }).then(response => {
                console.log('Jawaban tersimpan');
            }).catch(error => {
                console.error('Gagal menyimpan jawaban:', error);
            });
        }
    </script>
</body>
</html>