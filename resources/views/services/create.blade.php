<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Создать услугу</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background: #f7f7f7;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 24px;
            border-radius: 8px;
        }

        label {
            display: block;
            margin-top: 16px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            box-sizing: border-box;
        }

        .checkbox {
            width: auto;
        }

        button, a {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 14px;
            border: none;
            border-radius: 4px;
            background: #222;
            color: white;
            text-decoration: none;
            cursor: pointer;
        }

        .secondary {
            background: #666;
        }

        .error {
            color: #b91c1c;
            margin-top: 4px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Создать услугу</h1>

    <form action="{{ route('services.store') }}" method="POST">
        @csrf

        <label for="name">Название</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}">
        @error('name')
            <div class="error">{{ $message }}</div>
        @enderror

        <label for="duration_minutes">Длительность в минутах</label>
        <input type="number" id="duration_minutes" name="duration_minutes" value="{{ old('duration_minutes') }}">
        @error('duration_minutes')
            <div class="error">{{ $message }}</div>
        @enderror

        <label for="price">Цена</label>
        <input type="number" step="0.01" id="price" name="price" value="{{ old('price') }}">
        @error('price')
            <div class="error">{{ $message }}</div>
        @enderror

        <label>
            <input type="checkbox" class="checkbox" name="is_active" value="1" checked>
            Активна
        </label>

        <button type="submit">Сохранить</button>
        <a href="{{ route('services.index') }}" class="secondary">Назад</a>
    </form>
</div>
</body>
</html>