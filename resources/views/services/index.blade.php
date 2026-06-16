<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Услуги</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background: #f7f7f7;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 24px;
            border-radius: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
        }

        th {
            background: #eee;
            text-align: left;
        }

        a, button {
            display: inline-block;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            background: #222;
            color: white;
            text-decoration: none;
            cursor: pointer;
        }

        .danger {
            background: #b91c1c;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 16px;
        }

        .actions {
            display: flex;
            gap: 8px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Услуги салона</h1>

    @if (session('success'))
        <div class="success">
            {{ session('success') }}
        </div>
    @endif

    <a href="{{ route('services.create') }}">Добавить услугу</a>

    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Название</th>
            <th>Длительность</th>
            <th>Цена</th>
            <th>Активна</th>
            <th>Действия</th>
        </tr>
        </thead>

        <tbody>
        @forelse ($services as $service)
            <tr>
                <td>{{ $service->id }}</td>
                <td>{{ $service->name }}</td>
                <td>{{ $service->duration_minutes }} мин.</td>
                <td>{{ $service->price }} €</td>
                <td>{{ $service->is_active ? 'Да' : 'Нет' }}</td>
                <td>
                    <div class="actions">
                        <a href="{{ route('services.edit', $service) }}">Изменить</a>

                        <form action="{{ route('services.destroy', $service) }}" method="POST"
                              onsubmit="return confirm('Удалить услугу?')">
                            @csrf
                            @method('DELETE')

                            <button type="submit" class="danger">Удалить</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6">Услуг пока нет.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
</body>
</html>