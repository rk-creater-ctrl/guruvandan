@if (session('status'))
    <div class="flash success">{{ session('status') }}</div>
@endif

@if (isset($errors) && $errors->any())
    <div class="flash error">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif
