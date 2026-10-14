<?php include '../includes/header.php'; ?>
<header class="topbar public-topbar">
    <div class="brand"><img class="logo-img" src="../assets/images/logo_icon.png" alt="Abba House logo"><strong>Abba House</strong></div>
    <button class="icon-btn" onclick="openMenu()">☰</button>
</header>

<aside id="sideMenu" class="public-menu">
    <div class="menu-head"><div class="brand"><img class="logo-img" src="../assets/images/logo_icon.png" alt="Abba House logo"><strong>Abba House</strong></div><button onclick="closeMenu()">×</button></div>
    <a href="contact.php"><img src="../assets/images/phone_icon.png" width="30" height="30"> CONTACT US</a>
    <a href="signin.php"><img src="../assets/images/administrator_male.png" width="40" height="50" style="vertical-align: bottom";> ADMIN DASHBOARD</a>
</aside>
<div id="overlay" onclick="closeMenu()"></div>

<main class="page home-page">
    <section class="hero card">
        <small>NURTURING HOPE, BUILDING FUTURES</small>
        <h1>Every Child Deserves A Loving home</h1>
        <p>Abba House provides a safe haven for orphaned and vulnerable children, offering accommodation, education, healthcare, and the emotional support they need to thrive.</p>
    </section>

    <section>
        <h2>About Us</h2>
        <p>Abba House is a safe haven home for children who are in need of a safe home, or are available for adoption but not placed in their permanent homes. An estimate of 10,500 babies are abandoned or placed for adoption in South Africa, however only 1 out of every 3 babies survive abandonment and a tiny portion of those find a safe home to grow up in.</p>
        <p>Here at Abba House are working hard to provide a safe, Godly home for these babies as they wait for their future to begin.</p>
    </section>

    <section>
        <h2>OUR SERVICES</h2>
        <p>We provide holistic support to ensure every child in our care has the opportunity to thrive and reach their full potential.</p>
        <div class="horizontal-scroll">
            <div class="info-card"><img class="circle-icon-img" src="../assets/images/Accomodation_icon.png" alt=""><h3>Safe Accomodation</h3><p>Clean, comfortable living spaces where children can feel safe and secure, with proper supervision and care around the clock.</p></div>
            <div class="info-card"><img class="circle-icon-img" src="../assets/images/Dinner_icon.png" alt=""><h3>Meals & Nutrition</h3><p>Healthy meals daily, carefully planned to ensure proper nutrition for growing children’s physical development.</p></div>
            <div class="info-card"><img class="circle-icon-img" src="../assets/images/Support_icon.png" alt=""><h3>Emotional Support</h3><p>Psychological counseling and emotional care to help children process trauma and develop healthy coping mechanisms.</p></div>
            <div class="info-card"><img class="circle-icon-img" src="../assets/images/Healthcare_icon.png" alt=""><h3>Healthcare</h3><p>Regular health check-ups, medical care and access to healthcare professionals to keep children healthy and thriving.</p></div>
        </div>
    </section>

    <section>
        <h2>Get Involved</h2>
        <p>There are many ways you can make a difference in the lives of our children.</p>
        <div class="horizontal-scroll">
            <div class="info-card large"><img class="white-icon-img" src="../assets/images/Donation_icon.png" alt=""><h3>Make a donation</h3><p>Your financial contribution helps us provide food, shelter, education, and healthcare to children in need.</p></div>
            <div class="info-card large"><img class="white-icon-img" src="../assets/images/Gift_icon.png" alt=""><h3>Donate Supplies</h3><p>We accept donations of clothing, school supplies, food items, and other essential goods for their children.</p></div>
            <div class="info-card large"><img class="white-icon-img" src="../assets/images/Volunteer_icon.png" alt=""><h3>Volunteer</h3><p>Share your time and skills, we need tutors, mentors, and helpers to support our children’s development.</p></div>
            <div class="info-card large"><img class="white-icon-img" src="../assets/images/Sponsor_icon.png" alt=""><h3>Sponsor a child</h3><p>Become a regular supporter and help cover the ongoing costs of caring for a specific child.</p></div>
        </div>
    </section>
</main>
<?php include '../includes/footer.php'; ?>
