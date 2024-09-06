<style>
    .hero {
        background-color: var(--primary-color);
        color: #ffffff;
        padding: 6rem 0;
        text-align: center;
    }

    .hero h1 {
        font-size: 3.5rem;
        margin-bottom: 1rem;
    }

    .hero p {
        font-size: 1.5rem;
        margin-bottom: 2rem;
    }

    .hero-cta {
        display: inline-block;
        background-color: #ffffff;
        color: var(--primary-color);
        padding: 0.75rem 1.5rem;
        border-radius: 4px;
        text-decoration: none;
        font-weight: bold;
        transition: background-color 0.3s, color 0.3s;
    }

    .hero-cta:hover {
        background-color: var(--primary-dark);
        color: #ffffff;
    }

    .features {
        padding: 4rem 0;
        background-color: #ffffff;
    }

    .feature-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
    }

    .feature {
        text-align: center;
        padding: 2rem;
    }

    .feature i {
        font-size: 3rem;
        color: var(--primary-color);
        margin-bottom: 1rem;
    }

    .cta-section {
        background-color: var(--secondary-color);
        color: #ffffff;
        padding: 4rem 0;
        text-align: center;
    }

    .cta-section h2 {
        font-size: 2.5rem;
        margin-bottom: 1rem;
    }

    .cta-button {
        display: inline-block;
        background-color: #ffffff;
        color: var(--secondary-color);
        padding: 0.75rem 1.5rem;
        border-radius: 4px;
        text-decoration: none;
        font-weight: bold;
        margin-top: 1rem;
        transition: background-color 0.3s, color 0.3s;
    }

    .cta-button:hover {
        background-color: var(--primary-color);
        color: #ffffff;
    }
</style>

<div class="landing-page">
    <h1>Welcome to Our Modern Website</h1>
    <p>Experience the future of web design with our clean, minimalist approach. We focus on what matters most: your content.</p>
    <a href="/services" class="cta-button">Explore Our Services</a>
</div>