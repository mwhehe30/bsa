<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <meta
        http-equiv="X-UA-Compatible"
        content="ie=edge"
    >
    <title>Buweuk Sipit Academy</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            height: 100%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #1a2332;
        }

        .btn-login {
            display: inline-block;
            padding: 18px 70px;
            background: white;
            color: #1a2332;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: scale(1.05);
        }

        .btn-login i {
            margin-right: 10px;
        }

        @media (max-width: 480px) {
            .btn-login {
                padding: 15px 50px;
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>
    <a
        href="{{ route('login') }}"
        class="btn-login"
    >
        <i class="fas fa-sign-in-alt"></i> Masuk
    </a>
</body>

</html>
