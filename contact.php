<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MDRRMO - Municipal Disaster Risk Reduction and Management Office</title>
    <style>
        /* Global Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header Styles */
        header {
            background-color: #2c3e50;
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo img {
            height: 60px;
            margin-right: 15px;
        }
        
        .logo-text h1 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }
        
        .logo-text p {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 20px;
        }
        
        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        nav ul li a:hover {
            color: #3498db;
        }
        
        /* Hero Section */
        .hero {
            background-image: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://via.placeholder.com/1920x600');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            padding: 100px 20px;
        }
        
        .hero h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        
        .hero p {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto 30px;
        }
        
        .btn {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #2980b9;
        }
        
        /* Agencies Section */
        .agencies {
            padding: 60px 0;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .section-title h2 {
            font-size: 2rem;
            color: #2c3e50;
            position: relative;
            display: inline-block;
            padding-bottom: 10px;
        }
        
        .section-title h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: #3498db;
        }
        
        .agency-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }
        
        .agency-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        
        .agency-card:hover {
            transform: translateY(-10px);
        }
        
        .agency-image {
            height: 180px;
            background-color: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .agency-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .placeholder-text {
            color: #999;
            font-style: italic;
        }
        
        .agency-info {
            padding: 20px;
        }
        
        .agency-info h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .upload-btn {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 15px;
            transition: background-color 0.3s;
        }
        
        .upload-btn:hover {
            background-color: #2980b9;
        }
        
        .file-input {
            display: none;
        }
        
        /* Contact Section */
        .contact {
            background-color: #2c3e50;
            color: white;
            padding: 60px 0;
        }
        
        .contact-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
        }
        
        .contact-info h3, .contact-form h3 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .contact-info h3::after, .contact-form h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: #3498db;
        }
        
        .contact-details {
            margin-top: 20px;
        }
        
        .contact-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .contact-icon {
            margin-right: 15px;
            color: #3498db;
            font-size: 1.2rem;
        }
        
        .contact-text h4 {
            margin-bottom: 5px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .form-group textarea {
            height: 150px;
            resize: vertical;
        }
        
        .submit-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .submit-btn:hover {
            background-color: #2980b9;
        }
        
        /* Footer */
        footer {
            background-color: #1a252f;
            color: white;
            text-align: center;
            padding: 30px 0;
        }
        
        .social-links {
            margin: 20px 0;
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        
        .social-links a {
            color: white;
            font-size: 1.5rem;
            transition: color 0.3s, transform 0.3s;
            display: inline-block;
        }
        
        .social-links a:hover {
            color: #3498db;
            transform: translateY(-3px);
        }
        
        .footer-links {
            margin: 15px 0;
        }
        
        .footer-links a {
            color: white;
            margin: 0 10px;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: #3498db;
        }
        
        .copyright {
            margin-top: 20px;
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        /* Form Message Styles */
        .form-message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            display: none;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                text-align: center;
            }
            
            nav ul {
                margin-top: 20px;
                justify-content: center;
            }
            
            nav ul li {
                margin: 0 10px;
            }
            
            .hero h2 {
                font-size: 2rem;
            }
            
            .hero p {
                font-size: 1rem;
            }

            .social-links {
                gap: 15px;
            }
        }
    </style>
    <!-- Font Awesome for social media icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    

    <!-- Contact Section -->
    <section class="contact" id="contact">
        <div class="container">
            <div class="section-title">
                <h2 style="color: white;">Contact Us</h2>
            </div>
            <div class="contact-container">
                <div class="contact-info">
                    <h3>Get in Touch</h3>
                    <p>Have questions or need assistance? Reach out to us through any of these channels:</p>
                    
                    <div class="contact-details">
                        <div class="contact-item">
                            <div class="contact-icon">📍</div>
                            <div class="contact-text">
                                <h4>Address</h4>
                                <p>Jose P. Laurel., Street, Poblacion,Magsaysay, Occidental Mindoro</p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">📞</div>
                            <div class="contact-text">
                                <h4>Phone</h4>
                                <p>0909 965 3531</p>
                                <p>Emergency: 911 (local emergency number)</p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">✉️</div>
                            <div class="contact-text">
                                <h4>Email</h4>
                                <p>mdrrmo.mgy@gmail.com</p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">🕒</div>
                            <div class="contact-text">
                                <h4>Office Hours</h4>
                                <p>Monday to Friday: 8:00 AM - 5:00 PM</p>
                                <p>24/7 Emergency Response</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="contact-form">
                    <h3>Send Us a Message</h3>
                    <div id="form-messages" class="form-message"></div>
                    <form id="contactForm" method="post">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Your Message</label>
                            <textarea id="message" name="message" required></textarea>
                        </div>
                        
                        <button type="submit" class="submit-btn">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p><h3> Follow Us</h3></p>
            <div class="social-links">
                
                <a href="https://www.facebook.com/mdrrmo.magsaysay/" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="https://www.youtube.com/@qcfilms47/" target="_blank" rel="noopener noreferrer" aria-label="YouTube">
                    <i class="fab fa-youtube"></i>
                </a>
            </div>
             
            <div class="copyright">
                <p>&copy; 2023 Municipal Disaster Risk Reduction and Management Office. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        document.getElementById('contactForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formMessages = document.getElementById('form-messages');
            formMessages.style.display = 'none';
            
            const formData = {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                subject: document.getElementById('subject').value,
                message: document.getElementById('message').value
            };
            
            try {
                const response = await fetch('<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    formMessages.textContent = 'Thank you! Your message has been sent successfully.';
                    formMessages.className = 'form-message success';
                    document.getElementById('contactForm').reset();
                } else {
                    formMessages.textContent = `Error: ${result.error || 'Failed to send message'}`;
                    formMessages.className = 'form-message error';
                }
            } catch (error) {
                formMessages.textContent = 'Network error. Please try again later.';
                formMessages.className = 'form-message error';
            }
            
            formMessages.style.display = 'block';
            
            // Scroll to the message
            formMessages.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    </script>

    <?php
    // PHP code to handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get the raw POST data
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        // Validate required fields
        $required = ['name', 'email', 'subject', 'message'];
        $missing = [];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields: ' . implode(', ', $missing)]);
            exit;
        }

        // Sanitize inputs
        $name = htmlspecialchars($data['name']);
        $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        $subject = htmlspecialchars($data['subject']);
        $message = nl2br(htmlspecialchars($data['message']));

        // Your Resend API Key - replace with your actual key
        $apiKey = 're_17GhDMj4_463kn25ka9gYrdxHN39Pijyd';

        // Prepare the email data
        $emailData = [
            'from' => 'MDRRMO Contact Form <contact@mdrrmomagsaysay.com>', // Use your verified domain
            'to' => ['mdrrmo.mgy@gmail.com'],
            'subject' => "New Contact Form Submission: $subject",
            'html' => "
                <h2>New Contact Form Submission</h2>
                <p><strong>Name:</strong> $name</p>
                <p><strong>Email:</strong> $email</p>
                <p><strong>Subject:</strong> $subject</p>
                <p><strong>Message:</strong></p>
                <p>$message</p>
            "
        ];

        // Send the request to Resend API
        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            $error = json_decode($response, true);
            http_response_code($httpCode);
            echo json_encode(['error' => $error['message'] ?? 'Failed to send email']);
            exit;
        }

        // Success response
        echo json_encode(['success' => true]);
        exit;
    }
    ?>
</body>
</html>