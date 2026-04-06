<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Новая заявка</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827;">
    <h2 style="margin: 0 0 16px;">Новая заявка с формы контактов</h2>

    <p><strong>Имя:</strong> {{ $data['full_name'] ?? '—' }}</p>
    <p><strong>Email:</strong> {{ $data['email'] ?? '—' }}</p>
    <p><strong>Телефон:</strong> {{ $data['phone'] ?? '—' }}</p>
    <p><strong>Сообщение:</strong></p>
    <p style="white-space: pre-line;">{{ $data['message'] ?? '—' }}</p>
    <p><strong>Согласие на обработку ПДн:</strong> {{ !empty($data['personal_data_consent']) ? 'Да' : 'Нет' }}</p>
</body>
</html>
