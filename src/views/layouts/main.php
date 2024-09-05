<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'My App' ?></title>
    <!-- Milligram CSS for basic styling -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/milligram/1.4.1/milligram.min.css">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }

        main {
            flex: 1;
        }

        .container {
            max-width: 800px;
            padding: 0 20px;
        }

        nav ul {
            list-style: none;
            padding: 0;
        }

        nav ul li {
            display: inline;
            margin-right: 20px;
        }

        @media (max-width: 40rem) {
            nav ul li {
                display: block;
                margin-bottom: 10px;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <h1>My App</h1>
            <nav>
                <ul>
                    <li><a href="/">Home</a></li>
                    <li><a href="/about">About</a></li>
                    <li><a href="/contact">Contact</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            {{content}}
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> My App. All rights reserved.</p>
        </div>
    </footer>
</body>

</html>