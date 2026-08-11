<?php
session_start();

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| MODERN PUBLIC FRONT-END DASHBOARD
|--------------------------------------------------------------------------
*/
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<meta
    name="description"
    content="NISEL Online Education - Quality online lessons for Cambridge, IB and GES students."
>

<title>
NISEL ONLINE EDUCATION
</title>


<style>

/* =========================================================
   RESET
========================================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


html {
    scroll-behavior: smooth;
}


body {

    font-family:
        "Segoe UI",
        Arial,
        Helvetica,
        sans-serif;

    background: #f7faff;

    color: #1e293b;

    line-height: 1.6;

}


a {
    text-decoration: none;
}


img {
    max-width: 100%;
}


/* =========================================================
   VARIABLES
========================================================= */

:root {

    --primary: #003b70;

    --primary-light: #0877c9;

    --secondary: #00a6e8;

    --dark: #0f172a;

    --text: #475569;

    --light: #f7faff;

    --white: #ffffff;

    --border: #e2e8f0;

    --success: #16a34a;

}


/* =========================================================
   TOP BAR
========================================================= */

.top-bar {

    background: var(--primary);

    color: white;

    padding: 8px 6%;

    display: flex;

    justify-content: space-between;

    align-items: center;

    font-size: 13px;

}


.top-bar-left {

    display: flex;

    gap: 20px;

}


.top-bar-right {

    display: flex;

    gap: 15px;

}


.top-bar a {

    color: white;

}


/* =========================================================
   NAVIGATION
========================================================= */

.navbar {

    position: sticky;

    top: 0;

    z-index: 1000;

    background:
        rgba(255,255,255,.97);

    backdrop-filter:
        blur(10px);

    border-bottom:
        1px solid
        rgba(226,232,240,.8);

}


.nav-container {

    width: 90%;

    max-width: 1250px;

    margin: auto;

    height: 78px;

    display: flex;

    align-items: center;

    justify-content: space-between;

}


.logo {

    display: flex;

    align-items: center;

    gap: 12px;

}


.logo-icon {

    width: 46px;

    height: 46px;

    border-radius: 12px;

    background:
        linear-gradient(
            135deg,
            var(--primary),
            var(--secondary)
        );

    display: flex;

    align-items: center;

    justify-content: center;

    color: white;

    font-size: 22px;

    font-weight: 800;

}


.logo-text {

    color: var(--primary);

    font-weight: 900;

    font-size: 19px;

    line-height: 1.1;

}


.logo-text span {

    display: block;

    font-size: 10px;

    color: var(--secondary);

    letter-spacing: 1.8px;

    margin-top: 3px;

}


.nav-links {

    display: flex;

    align-items: center;

    gap: 26px;

    list-style: none;

}


.nav-links a {

    color: #334155;

    font-weight: 600;

    font-size: 14px;

    transition: .2s;

}


.nav-links a:hover {

    color: var(--secondary);

}


.nav-buttons {

    display: flex;

    align-items: center;

    gap: 8px;

}


.btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    padding: 11px 17px;

    border-radius: 8px;

    font-weight: 700;

    font-size: 14px;

    transition: .25s;

    border: none;

    cursor: pointer;

}


.btn-primary {

    background: var(--primary);

    color: white;

}


.btn-primary:hover {

    background: #002d55;

    transform: translateY(-2px);

}


.btn-outline {

    border:
        1.5px solid
        var(--primary);

    color: var(--primary);

    background: transparent;

}


.btn-outline:hover {

    background: var(--primary);

    color: white;

}


.menu-toggle {

    display: none;

    border: none;

    background: transparent;

    font-size: 27px;

    cursor: pointer;

    color: var(--primary);

}


/* =========================================================
   HERO SLIDESHOW
========================================================= */

.hero {

    position: relative;

    height: 640px;

    overflow: hidden;

}


.slide {

    position: absolute;

    inset: 0;

    opacity: 0;

    visibility: hidden;

    transition:
        opacity .8s ease;

}


.slide.active {

    opacity: 1;

    visibility: visible;

}


.slide-image {

    position: absolute;

    inset: 0;

    width: 100%;

    height: 100%;

    object-fit: cover;

}


.slide-overlay {

    position: absolute;

    inset: 0;

    background:
        linear-gradient(
            90deg,
            rgba(0,35,70,.92),
            rgba(0,48,95,.70),
            rgba(0,0,0,.15)
        );

}


.slide-content {

    position: relative;

    z-index: 2;

    width: 90%;

    max-width: 1250px;

    margin: auto;

    height: 100%;

    display: flex;

    flex-direction: column;

    justify-content: center;

    color: white;

    padding: 20px;

}


.slide-badge {

    display: inline-block;

    width: fit-content;

    padding: 7px 14px;

    border-radius: 30px;

    background:
        rgba(0,166,232,.2);

    border:
        1px solid
        rgba(255,255,255,.3);

    font-size: 13px;

    font-weight: 700;

    margin-bottom: 20px;

}


.slide-content h1 {

    font-size:
        clamp(38px, 5vw, 68px);

    line-height: 1.05;

    max-width: 750px;

    margin-bottom: 22px;

}


.slide-content h1 span {

    color: #5dd7ff;

}


.slide-content p {

    max-width: 650px;

    font-size: 18px;

    color: #e2e8f0;

    margin-bottom: 30px;

}


.hero-buttons {

    display: flex;

    gap: 12px;

    flex-wrap: wrap;

}


.btn-hero {

    padding: 14px 24px;

    border-radius: 8px;

    font-size: 15px;

}


.btn-hero-primary {

    background: #00a6e8;

    color: white;

}


.btn-hero-secondary {

    background: white;

    color: var(--primary);

}


/* =========================================================
   SLIDER CONTROLS
========================================================= */

.slider-arrow {

    position: absolute;

    top: 50%;

    transform: translateY(-50%);

    z-index: 5;

    width: 48px;

    height: 48px;

    border:
        1px solid
        rgba(255,255,255,.3);

    background:
        rgba(0,0,0,.25);

    color: white;

    border-radius: 50%;

    font-size: 22px;

    cursor: pointer;

    transition: .2s;

}


.slider-arrow:hover {

    background:
        rgba(0,166,232,.8);

}


.prev {

    left: 25px;

}


.next {

    right: 25px;

}


.slider-dots {

    position: absolute;

    bottom: 28px;

    left: 50%;

    transform: translateX(-50%);

    display: flex;

    gap: 8px;

    z-index: 5;

}


.dot {

    width: 10px;

    height: 10px;

    border-radius: 50%;

    border: none;

    background:
        rgba(255,255,255,.5);

    cursor: pointer;

}


.dot.active {

    width: 28px;

    border-radius: 10px;

    background: white;

}


/* =========================================================
   STATS
========================================================= */

.stats {

    width: 90%;

    max-width: 1100px;

    margin: -55px auto 0;

    position: relative;

    z-index: 10;

    background: white;

    border-radius: 15px;

    padding: 25px;

    box-shadow:
        0 15px 40px
        rgba(15,23,42,.12);

    display: grid;

    grid-template-columns:
        repeat(4,1fr);

}


.stat {

    text-align: center;

    border-right:
        1px solid
        var(--border);

}


.stat:last-child {

    border: none;

}


.stat h3 {

    color: var(--primary);

    font-size: 30px;

}


.stat p {

    color: var(--text);

    font-size: 13px;

}


/* =========================================================
   SECTION
========================================================= */

.section {

    padding: 90px 0;

}


.container {

    width: 90%;

    max-width: 1250px;

    margin: auto;

}


.section-heading {

    text-align: center;

    max-width: 700px;

    margin: 0 auto 45px;

}


.section-heading .eyebrow {

    color: var(--secondary);

    text-transform: uppercase;

    letter-spacing: 2px;

    font-size: 12px;

    font-weight: 800;

    margin-bottom: 10px;

}


.section-heading h2 {

    color: var(--primary);

    font-size:
        clamp(30px,4vw,44px);

    margin-bottom: 12px;

}


.section-heading p {

    color: var(--text);

}


/* =========================================================
   CURRICULUM
========================================================= */

.curriculum-grid {

    display: grid;

    grid-template-columns:
        repeat(3,1fr);

    gap: 24px;

}


.curriculum-card {

    position: relative;

    overflow: hidden;

    min-height: 340px;

    border-radius: 16px;

    color: white;

    display: flex;

    align-items: flex-end;

    padding: 28px;

    box-shadow:
        0 10px 30px
        rgba(0,0,0,.10);

}


.curriculum-card::before {

    content: "";

    position: absolute;

    inset: 0;

    background:
        linear-gradient(
            transparent,
            rgba(0,20,40,.9)
        );

}


.curriculum-bg {

    position: absolute;

    inset: 0;

    width: 100%;

    height: 100%;

    object-fit: cover;

    z-index: -1;

}


.curriculum-content {

    position: relative;

    z-index: 2;

}


.curriculum-content h3 {

    font-size: 25px;

    margin-bottom: 8px;

}


.curriculum-content p {

    color: #e2e8f0;

    font-size: 14px;

    margin-bottom: 15px;

}


/* =========================================================
   FEATURES
========================================================= */

.features {

    background: #eef7fc;

}


.features-grid {

    display: grid;

    grid-template-columns:
        repeat(3,1fr);

    gap: 22px;

}


.feature {

    background: white;

    border:
        1px solid
        var(--border);

    padding: 28px;

    border-radius: 14px;

    transition: .25s;

}


.feature:hover {

    transform:
        translateY(-5px);

    box-shadow:
        0 12px 30px
        rgba(0,0,0,.08);

}


.feature-icon {

    width: 54px;

    height: 54px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #e4f6ff;

    color: var(--secondary);

    border-radius: 12px;

    font-size: 25px;

    margin-bottom: 18px;

}


.feature h3 {

    color: var(--primary);

    margin-bottom: 8px;

}


.feature p {

    color: var(--text);

    font-size: 14px;

}


/* =========================================================
   LOGIN PORTAL
========================================================= */

.login-section {

    background:
        linear-gradient(
            180deg,
            #f8fbff,
            #eef7fc
        );

}


.login-grid {

    display: grid;

    grid-template-columns:
        repeat(3,1fr);

    gap: 24px;

}


.login-card {

    background: white;

    border:
        1px solid
        var(--border);

    border-radius: 18px;

    padding: 32px;

    text-align: center;

    box-shadow:
        0 8px 25px
        rgba(15,23,42,.06);

    transition: .25s;

}


.login-card:hover {

    transform:
        translateY(-6px);

    box-shadow:
        0 18px 40px
        rgba(15,23,42,.10);

}


.login-icon {

    width: 70px;

    height: 70px;

    margin: 0 auto 18px;

    border-radius: 18px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 32px;

    background:
        #e8f5ff;

}


.login-card h3 {

    color: var(--primary);

    margin-bottom: 10px;

    font-size: 21px;

}


.login-card p {

    color: var(--text);

    font-size: 14px;

    min-height: 68px;

    margin-bottom: 20px;

}


.login-card .btn {

    width: 100%;

}


/* =========================================================
   SUBJECTS
========================================================= */

.subject-grid {

    display: grid;

    grid-template-columns:
        repeat(4,1fr);

    gap: 15px;

}


.subject {

    background: white;

    border:
        1px solid
        var(--border);

    border-radius: 10px;

    padding: 20px;

    text-align: center;

    font-weight: 700;

    color: var(--primary);

    transition: .2s;

}


.subject:hover {

    border-color:
        var(--secondary);

    color:
        var(--secondary);

    transform:
        translateY(-2px);

}


/* =========================================================
   CTA
========================================================= */

.cta {

    background:
        linear-gradient(
            135deg,
            #003366,
            #0877c9
        );

    color: white;

    padding: 70px 0;

}


.cta-inner {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 30px;

}


.cta h2 {

    font-size:
        clamp(28px,4vw,42px);

    margin-bottom: 10px;

}


.cta p {

    color: #dbeafe;

}


/* =========================================================
   TESTIMONIALS
========================================================= */

.testimonial-grid {

    display: grid;

    grid-template-columns:
        repeat(3,1fr);

    gap: 22px;

}


.testimonial {

    background: white;

    border:
        1px solid
        var(--border);

    border-radius: 14px;

    padding: 25px;

}


.stars {

    color: #f59e0b;

    margin-bottom: 15px;

}


.testimonial p {

    color: var(--text);

    font-size: 14px;

    margin-bottom: 20px;

}


.student {

    display: flex;

    align-items: center;

    gap: 12px;

}


.avatar {

    width: 42px;

    height: 42px;

    border-radius: 50%;

    background: #dbeafe;

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: 800;

    color: var(--primary);

}


/* =========================================================
   FOOTER
========================================================= */

.footer {

    background: #071b30;

    color: #cbd5e1;

    padding: 65px 0 25px;

}


.footer-grid {

    display: grid;

    grid-template-columns:
        2fr 1fr 1fr 1fr;

    gap: 40px;

    margin-bottom: 45px;

}


.footer-brand h3 {

    color: white;

    font-size: 22px;

    margin-bottom: 10px;

}


.footer-brand p {

    font-size: 14px;

    max-width: 400px;

}


.footer h4 {

    color: white;

    margin-bottom: 15px;

}


.footer ul {

    list-style: none;

}


.footer li {

    margin-bottom: 8px;

}


.footer a {

    color: #cbd5e1;

    font-size: 14px;

}


.footer a:hover {

    color: #5dd7ff;

}


.footer-bottom {

    border-top:
        1px solid
        rgba(255,255,255,.1);

    padding-top: 20px;

    text-align: center;

    font-size: 13px;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:1000px) {

    .nav-links,
    .nav-buttons {

        display: none;

    }


    .menu-toggle {

        display: block;

    }


    .nav-links.mobile-open {

        display: flex;

        position: absolute;

        top: 78px;

        left: 0;

        right: 0;

        background: white;

        padding: 20px;

        flex-direction: column;

        align-items: flex-start;

        box-shadow:
            0 10px 20px
            rgba(0,0,0,.08);

    }


    .stats {

        grid-template-columns:
            repeat(2,1fr);

    }


    .stat:nth-child(2) {

        border: none;

    }


    .curriculum-grid,
    .features-grid,
    .login-grid,
    .testimonial-grid {

        grid-template-columns:
            repeat(2,1fr);

    }


    .subject-grid {

        grid-template-columns:
            repeat(3,1fr);

    }


    .footer-grid {

        grid-template-columns:
            repeat(2,1fr);

    }

}


@media(max-width:650px) {

    .top-bar {

        display: none;

    }


    .hero {

        height: 580px;

    }


    .slide-content {

        padding: 15px;

    }


    .slide-content p {

        font-size: 15px;

    }


    .slider-arrow {

        width: 38px;

        height: 38px;

    }


    .prev {

        left: 12px;

    }


    .next {

        right: 12px;

    }


    .stats {

        grid-template-columns:
            1fr 1fr;

        margin-top: -35px;

    }


    .stat {

        padding: 10px;

        border: none;

    }


    .section {

        padding: 65px 0;

    }


    .curriculum-grid,
    .features-grid,
    .login-grid,
    .testimonial-grid,
    .footer-grid {

        grid-template-columns: 1fr;

    }


    .subject-grid {

        grid-template-columns:
            repeat(2,1fr);

    }


    .cta-inner {

        flex-direction: column;

        align-items: flex-start;

    }

}

</style>

</head>


<body>


<!-- =========================================================
     TOP BAR
========================================================= -->

<div class="top-bar">

    <div class="top-bar-left">

        <span>
            📞 +233599363266 | +233540587025
        </span>

        <span>
            ✉️ info@niseleducation.online
        </span>

    </div>


    <div class="top-bar-right">

        <a href="#">
            Facebook
        </a>

        <a href="#">
            Instagram
        </a>

        <a href="#">
            YouTube
        </a>

    </div>

</div>


<!-- =========================================================
     NAVIGATION
========================================================= -->

<nav class="navbar">

<div class="nav-container">


<a
    href="index.php"
    class="logo"
>

    <div class="logo-icon">
        N
    </div>


    <div class="logo-text">

        NISEL

        <span>
            ONLINE EDUCATION
        </span>

    </div>

</a>


<ul
    class="nav-links"
    id="navLinks"
>

    <li>
        <a href="#home">
            Home
        </a>
    </li>

    <li>
        <a href="#about">
            About
        </a>
    </li>

    <li>
        <a href="#curricula">
            Curricula
        </a>
    </li>

    <li>
        <a href="#subjects">
            Subjects
        </a>
    </li>

    <li>
        <a href="#login">
            Login
        </a>
    </li>

    <li>
        <a href="#teachers">
            Teachers
        </a>
    </li>

   

</ul>


<!-- LOGIN BUTTONS -->

<div class="nav-buttons">

    <a
        href="student/login.php"
        class="btn btn-outline"
    >

        👨‍🎓 Student Login

    </a>


    <a
        href="teacher/login.php"
        class="btn btn-primary"
    >

        👨‍🏫 Teacher Login

    </a>

</div>


<button
    class="menu-toggle"
    id="menuToggle"
>

    ☰

</button>


</div>

</nav>


<!-- =========================================================
     HERO
========================================================= -->

<section
    class="hero"
    id="home"
>


<!-- SLIDE 1 -->

<div class="slide active">

    <img
        src="assets/images/slide1.jpg"
        class="slide-image"
        alt="Online Education"
        onerror="
            this.style.display='none';
            this.parentElement.style.background='linear-gradient(135deg,#003b70,#0877c9)';
        "
    >

    <div class="slide-overlay"></div>


    <div class="slide-content">

        <span class="slide-badge">

            🎓 QUALITY ONLINE EDUCATION

        </span>


        <h1>

            Learn Without
            <span>
                Limits.
            </span>

        </h1>


        <p>

            Connect with experienced teachers and
            receive personalised online lessons designed
            to help you achieve your academic goals.

        </p>


        <div class="hero-buttons">

            <a
                href="student/register.php"
                class="
                    btn
                    btn-hero
                    btn-hero-primary
                "
            >

                🚀 Start Learning

            </a>


            <a
                href="#curricula"
                class="
                    btn
                    btn-hero
                    btn-hero-secondary
            "
            >

                Explore Curricula

            </a>

        </div>

    </div>

</div>


<!-- SLIDE 2 -->

<div class="slide">

    <img
        src="assets/images/slide2.jpg"
        class="slide-image"
        alt="Online Teacher"
        onerror="
            this.style.display='none';
            this.parentElement.style.background='linear-gradient(135deg,#064e3b,#0ea5a5)';
        "
    >

    <div class="slide-overlay"></div>


    <div class="slide-content">

        <span class="slide-badge">

            👨‍🏫 EXPERIENCED TEACHERS

        </span>


        <h1>

            Learn From
            <span>
                The Best.
            </span>

        </h1>


        <p>

            Get access to dedicated teachers who
            understand your curriculum and are ready
            to guide you through every lesson.

        </p>


        <div class="hero-buttons">

            <a
                href="#teachers"
                class="
                    btn
                    btn-hero
                    btn-hero-primary
                "
            >

                Find Your Teacher

            </a>

        </div>

    </div>

</div>


<!-- SLIDE 3 -->

<div class="slide">

    <img
        src="assets/images/slide3.jpg"
        class="slide-image"
        alt="Online Classes"
        onerror="
            this.style.display='none';
            this.parentElement.style.background='linear-gradient(135deg,#581c87,#9333ea)';
        "
    >

    <div class="slide-overlay"></div>


    <div class="slide-content">

        <span class="slide-badge">

            🌍 CAMBRIDGE • IB • GES 

        </span>


        <h1>

            Your Education.
            <span>
                Your Future.
            </span>

        </h1>


        <p>

            Whether you follow Cambridge, IB or GES,
            NISEL provides flexible online learning
            built around your academic needs.

        </p>


        <div class="hero-buttons">

            <a
                href="student/register.php"
                class="
                    btn
                    btn-hero
                    btn-hero-primary
                "
            >

                Create Student Account

            </a>

        </div>

    </div>

</div>


<button
    class="slider-arrow prev"
    id="prevSlide"
>

    ‹

</button>


<button
    class="slider-arrow next"
    id="nextSlide"
>

    ›

</button>


<div class="slider-dots">

    <button
        class="dot active"
        data-slide="0"
    ></button>

    <button
        class="dot"
        data-slide="1"
    ></button>

    <button
        class="dot"
        data-slide="2"
    ></button>

</div>


</section>


<!-- =========================================================
     STATS
========================================================= -->

<div class="stats">

    <div class="stat">

        <h3>
            500+
        </h3>

        <p>
            Students
        </p>

    </div>


    <div class="stat">

        <h3>
            50+
        </h3>

        <p>
            Qualified Teachers
        </p>

    </div>


    <div class="stat">

        <h3>
            3
        </h3>

        <p>
            Major Curricula
        </p>

    </div>


    <div class="stat">

        <h3>
            8×
        </h3>

        <p>
            Lessons Per Month
        </p>

    </div>

</div>


<!-- =========================================================
     ABOUT
========================================================= -->

<section
    class="section"
    id="about"
>

<div class="container">


<div class="section-heading">

    <div class="eyebrow">
        About NISEL
    </div>

    <h2>
        Education Designed Around You
    </h2>

    <p>

        NISEL ONLINE EDUCATION connects students
        with qualified teachers for convenient,
        personalised online learning.

    </p>

</div>


<div class="features-grid">


<div class="feature">

    <div class="feature-icon">
        👨‍🏫
    </div>

    <h3>
        Qualified Teachers
    </h3>

    <p>

        Learn from teachers with experience in
        Cambridge, IB and GES curricula.

    </p>

</div>


<div class="feature">

    <div class="feature-icon">
        💻
    </div>

    <h3>
        Online Learning
    </h3>

    <p>

        Attend your lessons online from wherever
        you are using convenient virtual classes.

    </p>

</div>


<div class="feature">

    <div class="feature-icon">
        📅
    </div>

    <h3>
        Flexible Scheduling
    </h3>

    <p>

        Students can schedule their subjects around
        their available days and times.

    </p>

</div>


</div>

</div>

</section>


<!-- =========================================================
     CURRICULA
========================================================= -->

<section
    class="section"
    id="curricula"
    style="background:#f1f7fb;"
>

<div class="container">


<div class="section-heading">

    <div class="eyebrow">
        Our Curricula
    </div>

    <h2>
        Choose Your Curriculum
    </h2>

    <p>

        We support major international and Ghanaian
        educational pathways.

    </p>

</div>


<div class="curriculum-grid">


<div class="curriculum-card">

    <img
        src="assets/images/cambridge.jpg"
        class="curriculum-bg"
        alt="Cambridge"
        onerror="
            this.style.display='none';
            this.parentElement.style.background='linear-gradient(135deg,#003b70,#0877c9)';
        "
    >


    <div class="curriculum-content">

        <h3>
            Cambridge
        </h3>

        <p>

            Cambridge Primary, Lower Secondary,
            IGCSE and AS/A Level.

        </p>


        <a
            href="student/register.php"
            class="
                btn
                btn-hero-primary
            "
        >

            Explore Cambridge →

        </a>

    </div>

</div>


<div class="curriculum-card">

    <img
        src="assets/images/ib.jpg"
        class="curriculum-bg"
        alt="IB Curriculum"
        onerror="
            this.style.display='none';
            this.parentElement.style.background='linear-gradient(135deg,#7c2d12,#ea580c)';
        "
    >


    <div class="curriculum-content">

        <h3>
            IB Curriculum
        </h3>

        <p>

            International Baccalaureate learning
            support from primary to secondary.

        </p>


        <a
            href="student/register.php"
            class="
                btn
                btn-hero-primary
            "
        >

            Explore IB →

        </a>

    </div>

</div>


<div class="curriculum-card">

    <img
        src="assets/images/ges.jpg"
        class="curriculum-bg"
        alt="GES Curriculum"
        onerror="
            this.style.display='none';
            this.parentElement.style.background='linear-gradient(135deg,#14532d,#16a34a)';
        "
    >


    <div class="curriculum-content">

        <h3>
            GES Curriculum
        </h3>

        <p>

            Ghana Education Service curriculum
            support for learners across basic
            and secondary levels.

        </p>


        <a
            href="student/register.php"
            class="
                btn
                btn-hero-primary
            "
        >

            Explore GES →

        </a>

    </div>

</div>


</div>

</div>

</section>


<!-- =========================================================
     LOGIN PORTAL
========================================================= -->

<section
    class="section login-section"
    id="login"
>

<div class="container">


<div class="section-heading">

    <div class="eyebrow">
        NISEL PORTAL
    </div>

    <h2>
        Welcome Back
    </h2>

    <p>

        Select your account type to continue
        to your NISEL Online Education portal.

    </p>

</div>


<div class="login-grid">


<!-- STUDENT -->

<div class="login-card">

    <div class="login-icon">
        👨‍🎓
    </div>

    <h3>
        Student Portal
    </h3>

    <p>

        Login to book lessons, view your schedule,
        make payments and join your online classes.

    </p>


    <a
        href="student/login.php"
        class="btn btn-primary"
    >

        Student Login →

    </a>

</div>


<!-- TEACHER -->

<div class="login-card">

    <div class="login-icon">
        👨‍🏫
    </div>

    <h3>
        Teacher Portal
    </h3>

    <p>

        Login to view your students, manage your
        schedule and conduct your online lessons.

    </p>


    <a
        href="teacher/login.php"
        class="btn btn-primary"
    >

        Teacher Login →

    </a>

</div>


<!-- NEW STUDENT -->

<div class="login-card">

    <div class="login-icon">
        🚀
    </div>

    <h3>
        New Student?
    </h3>

    <p>

        Create your NISEL student account and
        start booking your subjects.

    </p>


    <a
        href="student/register.php"
        class="btn btn-primary"
    >

        Create Account →

    </a>

</div>


</div>

</div>

</section>


<!-- =========================================================
     SUBJECTS
========================================================= -->

<section
    class="section"
    id="subjects"
>

<div class="container">


<div class="section-heading">

    <div class="eyebrow">
        Popular Subjects
    </div>

    <h2>
        Learn What Matters
    </h2>

    <p>

        Choose from a wide range of academic subjects.

    </p>

</div>


<div class="subject-grid">


<div class="subject">
    📐 Mathematics
</div>

<div class="subject">
    ⚛️ Physics
</div>

<div class="subject">
    🧪 Chemistry
</div>

<div class="subject">
    🧬 Biology
</div>

<div class="subject">
    📚 English
</div>

<div class="subject">
    💻 Computer Science
</div>

<div class="subject">
    🌍 Geography
</div>

<div class="subject">
    💰 Economics
</div>

<div class="subject">
    📊 Accounting
</div>

<div class="subject">
    🏛️ Government
</div>

<div class="subject">
    📖 Literature
</div>

<div class="subject">
    🧠 Psychology
</div>

</div>

</div>

</section>


<!-- =========================================================
     CTA
========================================================= -->

<section class="cta">

<div class="container">

<div class="cta-inner">


<div>

<h2>
    Ready to Start Learning?
</h2>

<p>

    Create your account and book your first
    subject with a qualified NISEL teacher.

</p>

</div>


<div>

<a
    href="student/register.php"
    class="
        btn
        btn-hero
        btn-hero-secondary
    "
>

    🚀 Create Student Account

</a>

</div>


</div>

</div>

</section>


<!-- =========================================================
     FEATURED TEACHERS
========================================================= -->

<section
    class="section"
    id="teachers"
>

<div class="container">


<div class="section-heading">

    <div class="eyebrow">
        Meet Our Teachers
    </div>

    <h2>
        Learn From Experienced Teachers
    </h2>

    <p>

        Connect with dedicated teachers who are ready
        to guide you through your academic journey.

    </p>

</div>


<div class="teacher-grid">


<!-- TEACHER 1 -->

<div class="teacher-card">

    <div class="teacher-photo">

        <img
            src="assets/images/teachers/teacher1.jpg"
            alt="NISEL Teacher"
            onerror="
                this.style.display='none';
                this.parentElement.innerHTML='👨‍🏫';
            "
        >

    </div>


    <div class="teacher-info">

        <h3>
            Mr. Samuel Nyamekye
        </h3>

        <p class="teacher-role">
            Biology & Science
        </p>

        <span class="teacher-tag">
            PhD in Molecular Biology
        </span>

        <span class="teacher-tag">
            (IB, IGCSE, AS/A level)
        </span>

        <p class="teacher-description">

            Experienced online educator helping students
            develop strong understanding and confidence
            in Biology and Science.

        </p>


    </div>

</div>


<!-- TEACHER 2 -->

<div class="teacher-card">

    <div class="teacher-photo">

        <img
            src="assets/images/teachers/teacher2.jpg"
            alt="NISEL Teacher"
            onerror="
                this.style.display='none';
                this.parentElement.innerHTML='👩‍🏫';
            "
        >

    </div>


    <div class="teacher-info">

        <h3>
            Mrs. Mavis Agbakli
        </h3>

        <p class="teacher-role">
            English
        </p>

        <span class="teacher-tag">
           MPhil in English 
        </span>

        <span class="teacher-tag">
            (Cambridge AS/A Level, IGCSE, IB, GES) 
        </span>

        <p class="teacher-description">

            Helping learners improve their communication,
            comprehension, writing and examination skills.

        </p>


    </div>

</div>


<!-- TEACHER 3 -->

<div class="teacher-card">

    <div class="teacher-photo">

        <img
            src="assets/images/teachers/teacher3.jpg"
            alt="NISEL Teacher"
            onerror="
                this.style.display='none';
                this.parentElement.innerHTML='👨‍🏫';
            "
        >

    </div>


    <div class="teacher-info">

        <h3>
            Mr. Samuel Tenkorang
        </h3>

        <p class="teacher-role">
            Physics & Science
        </p>

        <span class="teacher-tag">
           BSc. Physics
        </span>

        <span class="teacher-tag">
             (Cambridge AS/A Level, IGCSE, IB)
        </span>

        <p class="teacher-description">

            Supporting students with clear explanations,
            practical understanding and examination
            preparation.
            
        </p>
        

    </div>

</div>


<!-- TEACHER 4 -->

<div class="teacher-card">

    <div class="teacher-photo">

        <img
            src="assets/images/teachers/teacher4.jpg"
            alt="NISEL Teacher"
            onerror="
                this.style.display='none';
                this.parentElement.innerHTML='👩‍🏫';
            "
        >

    </div>


    <div class="teacher-info">

        <h3>
            Miss. Linda Adu Mensah
        </h3>

        <p class="teacher-role">
            Economics & Business
        </p>

        <span class="teacher-tag">
            Masters in Public Administration
        </span>

        <span class="teacher-tag">
            (Cambridge AS/A Level, IB)
        </span>

        <p class="teacher-description">

            Guiding students through concepts, problem
            solving and examination-focused preparation.

        </p>

    </div>

</div>


</div>



</div>

</section>


<!-- =========================================================
     TESTIMONIALS
========================================================= -->

<section
    class="section"
    style="background:#f1f7fb;"
>

<div class="container">


<div class="section-heading">

    <div class="eyebrow">
        Student Experience
    </div>

    <h2>
        What Our Learners Say
    </h2>

</div>


<div class="testimonial-grid">


<div class="testimonial">

    <div class="stars">
        ★★★★★
    </div>

    <p>

        NISEL makes it easier for me to organise
        my lessons and study with my teachers online.

    </p>


    <div class="student">

        <div class="avatar">
            S
        </div>

        <div>

            <strong>
                Student
            </strong>

            <br>

            <small>
                Cambridge Programme
            </small>

        </div>

    </div>

</div>


<div class="testimonial">

    <div class="stars">
        ★★★★★
    </div>

    <p>

        I like the flexibility of online lessons
        because I can arrange my subjects around
        my school timetable.

    </p>


    <div class="student">

        <div class="avatar">
            A
        </div>

        <div>

            <strong>
                Student
            </strong>

            <br>

            <small>
                IGCSE
            </small>

        </div>

    </div>

</div>


<div class="testimonial">

    <div class="stars">
        ★★★★★
    </div>

    <p>

        The platform gives students a simple way
        to connect with teachers and manage lessons.

    </p>


    <div class="student">

        <div class="avatar">
            K
        </div>

        <div>

            <strong>
                Student
            </strong>

            <br>

            <small>
                GES Curriculum
            </small>

        </div>

    </div>

</div>


</div>

</div>

</section>


<!-- =========================================================
     FOOTER
========================================================= -->

<footer
    class="footer"
    id="contact"
>

<div class="container">


<div class="footer-grid">


<div class="footer-brand">

    <h3>
        NISEL ONLINE EDUCATION
    </h3>

    <p>

        Providing flexible and personalised online
        education for students following Cambridge,
        IB and GES curricula.

    </p>

</div>


<div>

    <h4>
        Platform
    </h4>

    <ul>

        <li>
            <a href="#about">
                About Us
            </a>
        </li>

        <li>
            <a href="#curricula">
                Curricula
            </a>
        </li>

        <li>
            <a href="#subjects">
                Subjects
            </a>
        </li>

        <li>
            <a href="#teachers">
                Teachers
            </a>
        </li>

    </ul>

</div>


<div>

    <h4>
        Account
    </h4>

    <ul>

        <li>
            <a href="student/login.php">
                Student Login
            </a>
        </li>

        <li>
            <a href="teacher/login.php">
                Teacher Login
            </a>
        </li>

        <li>
            <a href="student/register.php">
                Student Registration
            </a>
        </li>

        <li>
            <a href="teacher/teacher_apply.php">
                Become a Teacher
            </a>
        </li>

    </ul>

</div>


<div>

    <h4>
        Contact
    </h4>

    <ul>

        <li>
            📞 +233599363266      +233540587025
        </li>

        <li>
            📧 info@niseleducation.online
        </li>

        <li>
            GH Ghana
        </li>

    </ul>

</div>


</div>


<div class="footer-bottom">

    © <?= date('Y') ?>

    NISEL ONLINE EDUCATION.

    All Rights Reserved.

</div>


</div>

</footer>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

/* =========================================================
   MOBILE MENU
========================================================= */

const menuToggle =
    document.getElementById("menuToggle");

const navLinks =
    document.getElementById("navLinks");


menuToggle.addEventListener(
    "click",
    function() {

        navLinks.classList.toggle(
            "mobile-open"
        );

    }
);


/* =========================================================
   SLIDESHOW
========================================================= */

const slides =
    document.querySelectorAll(".slide");

const dots =
    document.querySelectorAll(".dot");

const nextButton =
    document.getElementById("nextSlide");

const prevButton =
    document.getElementById("prevSlide");


let currentSlide = 0;

let slideTimer;


/* =========================================================
   SHOW SLIDE
========================================================= */

function showSlide(index)
{

    if (
        index >= slides.length
    ) {

        index = 0;

    }


    if (
        index < 0
    ) {

        index =
            slides.length - 1;

    }


    slides.forEach(
        function(slide) {

            slide.classList.remove(
                "active"
            );

        }
    );


    dots.forEach(
        function(dot) {

            dot.classList.remove(
                "active"
            );

        }
    );


    slides[index].classList.add(
        "active"
    );


    dots[index].classList.add(
        "active"
    );


    currentSlide = index;

}


/* =========================================================
   NEXT
========================================================= */

function nextSlide()
{

    showSlide(
        currentSlide + 1
    );

}


/* =========================================================
   PREVIOUS
========================================================= */

function previousSlide()
{

    showSlide(
        currentSlide - 1
    );

}


/* =========================================================
   CONTROLS
========================================================= */

nextButton.addEventListener(
    "click",
    function() {

        nextSlide();

        restartTimer();

    }
);


prevButton.addEventListener(
    "click",
    function() {

        previousSlide();

        restartTimer();

    }
);


/* =========================================================
   DOTS
========================================================= */

dots.forEach(
    function(dot, index) {

        dot.addEventListener(
            "click",
            function() {

                showSlide(index);

                restartTimer();

            }
        );

    }
);


/* =========================================================
   AUTO SLIDESHOW
========================================================= */

function startTimer()
{

    slideTimer =
        setInterval(
            nextSlide,
            6000
        );

}


function restartTimer()
{

    clearInterval(
        slideTimer
    );

    startTimer();

}


startTimer();


/* =========================================================
   CLOSE MOBILE MENU
========================================================= */

document
    .querySelectorAll(
        ".nav-links a"
    )
    .forEach(
        function(link) {

            link.addEventListener(
                "click",
                function() {

                    navLinks.classList.remove(
                        "mobile-open"
                    );

                }
            );

        }
    );

</script>


</body>

</html>
