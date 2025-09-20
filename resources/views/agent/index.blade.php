<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            padding: 2rem;
        }
        .tab-button.active {
            border-color: #3b82f6;
            color: #2563eb;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-4xl font-bold text-gray-800">Agent Dashboard</h1>
        <div id="auth-status" class="text-sm text-gray-600 rounded-full px-4 py-2 bg-white shadow-sm">
            <span id="user-id-display">Loading...</span>
        </div>
    </div>

    <!-- Dashboard Widgets -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-lg font-medium text-gray-500 mb-2">Total Packages</h2>
            <p id="total-packages" class="text-3xl font-semibold text-gray-900">0</p>
        </div>
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-lg font-medium text-gray-500 mb-2">Total Bookings</h2>
            <p id="total-bookings" class="text-3xl font-semibold text-gray-900">0</p>
        </div>
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-lg font-medium text-gray-500 mb-2">Pending Reviews</h2>
            <p id="pending-reviews" class="text-3xl font-semibold text-gray-900">0</p>
        </div>
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-lg font-medium text-gray-500 mb-2">Upcoming Trips</h2>
            <p id="upcoming-trips" class="text-3xl font-semibold text-gray-900">0</p>
        </div>
    </div>

    <!-- Tabs for different sections -->
    <div class="flex border-b border-gray-200 mb-6">
        <button onclick="showTab('bookings')" class="tab-button px-4 py-2 -mb-px text-lg font-medium border-b-2 border-transparent hover:text-blue-600 hover:border-blue-600 focus:outline-none transition-colors duration-200" data-target="bookings">Bookings</button>
        <button onclick="showTab('packages')" class="tab-button px-4 py-2 -mb-px text-lg font-medium border-b-2 border-transparent hover:text-blue-600 hover:border-blue-600 focus:outline-none transition-colors duration-200" data-target="packages">Packages</button>
        <button onclick="showTab('content')" class="tab-button px-4 py-2 -mb-px text-lg font-medium border-b-2 border-transparent hover:text-blue-600 hover:border-blue-600 focus:outline-none transition-colors duration-200" data-target="content">Content Management</button>
        <button onclick="showTab('reviews')" class="tab-button px-4 py-2 -mb-px text-lg font-medium border-b-2 border-transparent hover:text-blue-600 hover:border-blue-600 focus:outline-none transition-colors duration-200" data-target="reviews">Review Moderation</button>
    </div>

    <div id="message-box" class="hidden p-4 mb-4 text-sm text-center text-blue-700 bg-blue-100 rounded-lg"></div>

    <!-- Bookings Tab -->
    <div id="bookings-tab" class="tab-content hidden">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-gray-900">Bookings</h2>
                <button onclick="document.getElementById('add-booking-modal').classList.remove('hidden')" class="bg-blue-600 text-white rounded-full px-6 py-2 font-medium shadow-md hover:bg-blue-700 transition-colors duration-200">
                    Add New Booking
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Package</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="bookings-list" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="6" class="text-center py-4">No bookings found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Packages Tab -->
    <div id="packages-tab" class="tab-content hidden">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-gray-900">Holiday Packages</h2>
                <button onclick="document.getElementById('add-package-modal').classList.remove('hidden')" class="bg-blue-600 text-white rounded-full px-6 py-2 font-medium shadow-md hover:bg-blue-700 transition-colors duration-200">
                    Create New Package
                </button>
            </div>
            <div id="packages-list" class="space-y-6">
                <div class="text-center text-gray-500 py-4">No packages found.</div>
            </div>
        </div>
    </div>

    <!-- Content Management Tab -->
    <div id="content-tab" class="tab-content hidden">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-900 mb-6">Content Management</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Destinations -->
                <div class="bg-gray-50 rounded-lg p-4 shadow-sm">
                    <h3 class="text-xl font-medium text-gray-700 mb-4">Destinations</h3>
                    <ul id="destinations-list" class="list-disc list-inside space-y-2 mb-4"></ul>
                    <button onclick="document.getElementById('add-destination-modal').classList.remove('hidden')" class="bg-green-500 text-white rounded-full px-4 py-2 text-sm hover:bg-green-600 w-full">Add Destination</button>
                </div>
                <!-- Accommodations -->
                <div class="bg-gray-50 rounded-lg p-4 shadow-sm">
                    <h3 class="text-xl font-medium text-gray-700 mb-4">Accommodations</h3>
                    <ul id="accommodations-list" class="list-disc list-inside space-y-2 mb-4"></ul>
                    <button onclick="document.getElementById('add-accommodation-modal').classList.remove('hidden')" class="bg-green-500 text-white rounded-full px-4 py-2 text-sm hover:bg-green-600 w-full">Add Accommodation</button>
                </div>
                <!-- Activities -->
                <div class="bg-gray-50 rounded-lg p-4 shadow-sm">
                    <h3 class="text-xl font-medium text-gray-700 mb-4">Activities</h3>
                    <ul id="activities-list" class="list-disc list-inside space-y-2 mb-4"></ul>
                    <button onclick="document.getElementById('add-activity-modal').classList.remove('hidden')" class="bg-green-500 text-white rounded-full px-4 py-2 text-sm hover:bg-green-600 w-full">Add Activity</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reviews Tab -->
    <div id="reviews-tab" class="tab-content hidden">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-900 mb-6">Pending Reviews</h2>
            <div id="reviews-list" class="space-y-6">
                <div class="text-center text-gray-500 py-4">No pending reviews to moderate.</div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for adding a new booking -->
<div id="add-booking-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-8 border w-96 shadow-lg rounded-xl bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold text-gray-900">Add New Booking</h3>
            <button onclick="document.getElementById('add-booking-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form id="add-booking-form" class="space-y-4">
            <div>
                <label for="client-name" class="block text-sm font-medium text-gray-700">Client Name</label>
                <input type="text" id="client-name" name="client_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label for="package-select" class="block text-sm font-medium text-gray-700">Select Package</label>
                <select id="package-select" name="package-select" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></select>
            </div>
            <div>
                <label for="booking-date" class="block text-sm font-medium text-gray-700">Booking Date</label>
                <input type="date" id="booking-date" name="booking_date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex justify-end pt-4">
                <button type="button" onclick="document.getElementById('add-booking-modal').classList.add('hidden')" class="bg-gray-200 text-gray-700 rounded-full px-6 py-2 font-medium mr-2 hover:bg-gray-300 transition-colors duration-200">Cancel</button>
                <button type="submit" class="bg-blue-600 text-white rounded-full px-6 py-2 font-medium shadow-md hover:bg-blue-700 transition-colors duration-200">Create Booking</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal for creating a new package -->
<div id="add-package-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-8 border w-3/4 max-w-2xl shadow-lg rounded-xl bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold text-gray-900">Create New Holiday Package</h3>
            <button onclick="document.getElementById('add-package-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form id="add-package-form" class="space-y-4">
            <div>
                <label for="package-name" class="block text-sm font-medium text-gray-700">Package Name</label>
                <input type="text" id="package-name" name="package_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label for="package-description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea id="package-description" name="package_description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
            </div>
            <div>
                <label for="package-destination" class="block text-sm font-medium text-gray-700">Select Destination</label>
                <select id="package-destination" name="package_destination" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Select Accommodations (Hold Ctrl/Cmd to select multiple)</label>
                <select id="package-accommodations" name="package_accommodations" multiple class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 h-32"></select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Select Activities (Hold Ctrl/Cmd to select multiple)</label>
                <select id="package-activities" name="package_activities" multiple class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 h-32"></select>
            </div>
            <div class="flex justify-end pt-4">
                <button type="button" onclick="document.getElementById('add-package-modal').classList.add('hidden')" class="bg-gray-200 text-gray-700 rounded-full px-6 py-2 font-medium mr-2 hover:bg-gray-300 transition-colors duration-200">Cancel</button>
                <button type="submit" class="bg-blue-600 text-white rounded-full px-6 py-2 font-medium shadow-md hover:bg-blue-700 transition-colors duration-200">Create Package</button>
            </div>
        </form>
    </div>
</div>

<!-- Modals for adding content -->
<div id="add-destination-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-8 border w-96 shadow-lg rounded-xl bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold text-gray-900">Add New Destination</h3>
            <button onclick="document.getElementById('add-destination-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form id="add-destination-form" class="space-y-4">
            <div>
                <label for="destination-name" class="block text-sm font-medium text-gray-700">Destination Name</label>
                <input type="text" id="destination-name" name="destination_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex justify-end pt-4">
                <button type="submit" class="bg-green-500 text-white rounded-full px-6 py-2 font-medium shadow-md hover:bg-green-600">Save</button>
            </div>
        </form>
    </div>
</div>

<div id="add-accommodation-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-8 border w-96 shadow-lg rounded-xl bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold text-gray-900">Add New Accommodation</h3>
            <button onclick="document.getElementById('add-accommodation-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form id="add-accommodation-form" class="space-y-4">
            <div>
                <label for="accommodation-name" class="block text-sm font-medium text-gray-700">Accommodation Name</label>
                <input type="text" id="accommodation-name" name="accommodation_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex justify-end pt-4">
                <button type="submit" class="bg-green-500 text-white rounded-full px-6 py-2 font-medium shadow-md hover:bg-green-600">Save</button>
            </div>
        </form>
    </div>
</div>

<div id="add-activity-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-8 border w-96 shadow-lg rounded-xl bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold text-gray-900">Add New Activity</h3>
            <button onclick="document.getElementById('add-activity-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form id="add-activity-form" class="space-y-4">
            <div>
                <label for="activity-name" class="block text-sm font-medium text-gray-700">Activity Name</label>
                <input type="text" id="activity-name" name="activity_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex justify-end pt-4">
                <button type="submit" class="bg-green-500 text-white rounded-full px-6 py-2 font-medium shadow-md hover:bg-green-600">Save</button>
            </div>
        </form>
    </div>
</div>

<script type="module">
    import { initializeApp } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-app.js";
    import { getAuth, signInAnonymously, signInWithCustomToken, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-auth.js";
    import { getFirestore, doc, getDoc, addDoc, setDoc, updateDoc, deleteDoc, onSnapshot, collection, query, where, getDocs } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-firestore.js";

    // Global variables for Firebase configuration provided by the environment
    const appId = typeof __app_id !== 'undefined' ? __app_id : 'default-app-id';
    const firebaseConfig = typeof __firebase_config !== 'undefined' ? JSON.parse(__firebase_config) : {};
    const initialAuthToken = typeof __initial_auth_token !== 'undefined' ? __initial_auth_token : null;

    let app, auth, db, userId;
    const messageBox = document.getElementById('message-box');
    const authStatusSpan = document.getElementById('user-id-display');

    // In-memory data storage for faster access
    let destinations = [];
    let accommodations = [];
    let activities = [];
    let packages = [];

    function showMessage(message, type = 'info') {
        messageBox.textContent = message;
        messageBox.className = `p-4 mb-4 text-sm text-center rounded-lg ${
            type === 'success' ? 'bg-green-100 text-green-700' :
            type === 'error' ? 'bg-red-100 text-red-700' :
            'bg-blue-100 text-blue-700'
        }`;
        messageBox.classList.remove('hidden');
        setTimeout(() => messageBox.classList.add('hidden'), 5000);
    }

    function showTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));
        document.getElementById(`${tabId}-tab`).classList.remove('hidden');

        document.querySelectorAll('.tab-button').forEach(button => {
            if (button.dataset.target === tabId) {
                button.classList.add('active');
            } else {
                button.classList.remove('active');
            }
        });
    }

    // Function to initialize Firebase and auth
    const initializeFirebase = async () => {
        try {
            app = initializeApp(firebaseConfig);
            db = getFirestore(app);
            auth = getAuth(app);

            onAuthStateChanged(auth, async (user) => {
                if (user) {
                    userId = user.uid;
                    authStatusSpan.textContent = `User ID: ${userId}`;

                    // Start listening to collections only after auth is ready
                    listenToDestinations();
                    listenToAccommodations();
                    listenToActivities();
                    listenToPackages();
                    listenToBookings();
                    listenToReviews();
                } else {
                    showMessage('Signing in...', 'info');
                    try {
                        if (initialAuthToken) {
                            await signInWithCustomToken(auth, initialAuthToken);
                        } else {
                            await signInAnonymously(auth);
                        }
                    } catch (error) {
                        showMessage('Authentication failed. Check your Firebase config.', 'error');
                        console.error("Firebase Auth Error:", error);
                    }
                }
            });

        } catch (e) {
            showMessage('Failed to initialize Firebase. Check your configuration.', 'error');
            console.error("Firebase initialization error:", e);
        }
    };

    // --- Content Management Listeners & Functions ---

    // Real-time listener for destinations
    const listenToDestinations = () => {
        const destinationsRef = collection(db, `artifacts/${appId}/public/data/destinations`);
        onSnapshot(destinationsRef, (snapshot) => {
            destinations = [];
            const destinationsList = document.getElementById('destinations-list');
            destinationsList.innerHTML = '';
            snapshot.forEach(doc => {
                const data = doc.data();
                destinations.push({ id: doc.id, ...data });
                destinationsList.innerHTML += `<li>${data.name}</li>`;
            });
            // Update dropdowns that use this data
            populatePackageSelects();
        }, (error) => {
            console.error("Error fetching destinations:", error);
            showMessage('Error loading destinations.', 'error');
        });
    };

    // Real-time listener for accommodations
    const listenToAccommodations = () => {
        const accommodationsRef = collection(db, `artifacts/${appId}/public/data/accommodations`);
        onSnapshot(accommodationsRef, (snapshot) => {
            accommodations = [];
            const accommodationsList = document.getElementById('accommodations-list');
            accommodationsList.innerHTML = '';
            snapshot.forEach(doc => {
                const data = doc.data();
                accommodations.push({ id: doc.id, ...data });
                accommodationsList.innerHTML += `<li>${data.name}</li>`;
            });
            populatePackageSelects();
        }, (error) => {
            console.error("Error fetching accommodations:", error);
            showMessage('Error loading accommodations.', 'error');
        });
    };

    // Real-time listener for activities
    const listenToActivities = () => {
        const activitiesRef = collection(db, `artifacts/${appId}/public/data/activities`);
        onSnapshot(activitiesRef, (snapshot) => {
            activities = [];
            const activitiesList = document.getElementById('activities-list');
            activitiesList.innerHTML = '';
            snapshot.forEach(doc => {
                const data = doc.data();
                activities.push({ id: doc.id, ...data });
                activitiesList.innerHTML += `<li>${data.name}</li>`;
            });
            populatePackageSelects();
        }, (error) => {
            console.error("Error fetching activities:", error);
            showMessage('Error loading activities.', 'error');
        });
    };

    // Populates the select dropdowns in the package creation modal
    const populatePackageSelects = () => {
        const destSelect = document.getElementById('package-destination');
        destSelect.innerHTML = '';
        destinations.forEach(d => {
            destSelect.innerHTML += `<option value="${d.id}">${d.name}</option>`;
        });

        const accomSelect = document.getElementById('package-accommodations');
        accomSelect.innerHTML = '';
        accommodations.forEach(a => {
            accomSelect.innerHTML += `<option value="${a.id}">${a.name}</option>`;
        });

        const activitySelect = document.getElementById('package-activities');
        activitySelect.innerHTML = '';
        activities.forEach(a => {
            activitySelect.innerHTML += `<option value="${a.id}">${a.name}</option>`;
        });

        const bookingPackageSelect = document.getElementById('package-select');
        bookingPackageSelect.innerHTML = '';
        packages.forEach(p => {
            bookingPackageSelect.innerHTML += `<option value="${p.id}">${p.name}</option>`;
        });
    };

    // Add a new destination
    window.addDestination = async (event) => {
        event.preventDefault();
        const form = event.target;
        const name = form['destination_name'].value;
        try {
            const docRef = await addDoc(collection(db, `artifacts/${appId}/public/data/destinations`), { name });
            showMessage(`Destination added successfully!`, 'success');
            document.getElementById('add-destination-modal').classList.add('hidden');
            form.reset();
        } catch (e) {
            showMessage('Failed to add destination.', 'error');
            console.error("Error adding destination: ", e);
        }
    };

    // Add a new accommodation
    window.addAccommodation = async (event) => {
        event.preventDefault();
        const form = event.target;
        const name = form['accommodation_name'].value;
        try {
            const docRef = await addDoc(collection(db, `artifacts/${appId}/public/data/accommodations`), { name });
            showMessage(`Accommodation added successfully!`, 'success');
            document.getElementById('add-accommodation-modal').classList.add('hidden');
            form.reset();
        } catch (e) {
            showMessage('Failed to add accommodation.', 'error');
            console.error("Error adding accommodation: ", e);
        }
    };

    // Add a new activity
    window.addActivity = async (event) => {
        event.preventDefault();
        const form = event.target;
        const name = form['activity_name'].value;
        try {
            const docRef = await addDoc(collection(db, `artifacts/${appId}/public/data/activities`), { name });
            showMessage(`Activity added successfully!`, 'success');
            document.getElementById('add-activity-modal').classList.add('hidden');
            form.reset();
        } catch (e) {
            showMessage('Failed to add activity.', 'error');
            console.error("Error adding activity: ", e);
        }
    };

    // Create a new package
    window.addPackage = async (event) => {
        event.preventDefault();
        const form = event.target;
        const name = form['package_name'].value;
        const description = form['package_description'].value;
        const destinationId = form['package_destination'].value;

        // Get selected accommodation and activity IDs
        const selectedAccommodations = Array.from(form['package_accommodations'].selectedOptions).map(option => option.value);
        const selectedActivities = Array.from(form['package_activities'].selectedOptions).map(option => option.value);

        try {
            const docRef = await addDoc(collection(db, `artifacts/${appId}/public/data/packages`), {
                name,
                description,
                destinationId,
                accommodationIds: selectedAccommodations,
                activityIds: selectedActivities,
                agentId: userId,
                createdAt: new Date().toISOString()
            });
            showMessage(`Package created successfully!`, 'success');
            document.getElementById('add-package-modal').classList.add('hidden');
            form.reset();
        } catch (e) {
            showMessage('Failed to create package.', 'error');
            console.error("Error creating package: ", e);
        }
    };

    // --- Bookings & Reviews Listeners (Updated) ---

    // Real-time listener for packages
    const listenToPackages = () => {
        const packagesRef = collection(db, `artifacts/${appId}/public/data/packages`);
        onSnapshot(packagesRef, (snapshot) => {
            packages = [];
            const packagesList = document.getElementById('packages-list');
            packagesList.innerHTML = '';
            document.getElementById('total-packages').textContent = snapshot.size;

            if (snapshot.empty) {
                packagesList.innerHTML = `<div class="text-center text-gray-500 py-4">No packages found.</div>`;
            } else {
                snapshot.forEach(doc => {
                    const data = doc.data();
                    packages.push({ id: doc.id, ...data });
                    const destination = destinations.find(d => d.id === data.destinationId)?.name || 'Unknown';
                    const packageCard = `
                        <div class="bg-gray-100 rounded-lg p-4 shadow-sm">
                            <h3 class="text-xl font-semibold text-gray-900">${data.name}</h3>
                            <p class="text-gray-600">${data.description}</p>
                            <p class="text-sm text-gray-500 mt-2">Destination: <span class="font-medium">${destination}</span></p>
                        </div>
                    `;
                    packagesList.innerHTML += packageCard;
                });
            }
            populatePackageSelects();
        }, (error) => {
            console.error("Error fetching packages:", error);
            showMessage('Error loading packages.', 'error');
        });
    };

    // Real-time listener for bookings
    const listenToBookings = () => {
        const bookingsRef = collection(db, `artifacts/${appId}/public/data/bookings`);
        onSnapshot(bookingsRef, (snapshot) => {
            const bookingsList = document.getElementById('bookings-list');
            bookingsList.innerHTML = '';
            let totalBookings = 0;
            let upcomingTrips = 0;

            if (snapshot.empty) {
                bookingsList.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-gray-500">No bookings found.</td></tr>`;
                document.getElementById('total-bookings').textContent = 0;
                document.getElementById('upcoming-trips').textContent = 0;
                return;
            }

            snapshot.forEach(doc => {
                const booking = doc.data();
                const bookingId = doc.id;
                const bookingDate = booking.date ? new Date(booking.date).toLocaleDateString() : 'N/A';
                const now = new Date();
                const tripDate = new Date(booking.date);
                const packageName = packages.find(p => p.id === booking.packageId)?.name || 'Unknown';

                if (tripDate > now) {
                    upcomingTrips++;
                }

                totalBookings++;

                const row = `
                    <tr class="hover:bg-gray-100 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${bookingId.substring(0, 8)}...</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${booking.clientName}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${packageName}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${bookingDate}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                                booking.status === 'confirmed' ? 'bg-green-100 text-green-800' :
                                booking.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                'bg-red-100 text-red-800'
                            }">
                                ${booking.status}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="updateBookingStatus('${bookingId}', 'confirmed')" class="text-green-600 hover:text-green-900 px-2 py-1">Confirm</button>
                            <button onclick="updateBookingStatus('${bookingId}', 'canceled')" class="text-red-600 hover:text-red-900 px-2 py-1">Cancel</button>
                        </td>
                    </tr>
                `;
                bookingsList.innerHTML += row;
            });
            document.getElementById('total-bookings').textContent = totalBookings;
            document.getElementById('upcoming-trips').textContent = upcomingTrips;
        }, (error) => {
            console.error("Error fetching bookings:", error);
            showMessage('Error loading bookings. Please check your permissions.', 'error');
        });
    };

    // Real-time listener for reviews
    const listenToReviews = () => {
        const reviewsRef = query(collection(db, `artifacts/${appId}/public/data/reviews`), where('status', '==', 'pending'));
        onSnapshot(reviewsRef, (snapshot) => {
            const reviewsList = document.getElementById('reviews-list');
            reviewsList.innerHTML = '';
            document.getElementById('pending-reviews').textContent = snapshot.size;

            if (snapshot.empty) {
                reviewsList.innerHTML = `<div class="text-center text-gray-500 py-4">No pending reviews to moderate.</div>`;
                return;
            }

            snapshot.forEach(doc => {
                const review = doc.data();
                const reviewId = doc.id;
                const reviewCard = `
                    <div class="bg-gray-50 rounded-lg p-4 shadow-sm">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-lg font-semibold text-gray-900">${review.title || 'No Title'}</span>
                            <div class="text-yellow-400">
                                ${'⭐'.repeat(review.rating || 0)}
                            </div>
                        </div>
                        <p class="text-gray-700 mb-4">${review.comment}</p>
                        <div class="flex justify-end space-x-2">
                            <button onclick="updateReviewStatus('${reviewId}', 'approved')" class="bg-green-500 text-white rounded-full px-4 py-1 text-sm hover:bg-green-600">Approve</button>
                            <button onclick="updateReviewStatus('${reviewId}', 'rejected')" class="bg-red-500 text-white rounded-full px-4 py-1 text-sm hover:bg-red-600">Reject</button>
                        </div>
                    </div>
                `;
                reviewsList.innerHTML += reviewCard;
            });
        }, (error) => {
            console.error("Error fetching reviews:", error);
            showMessage('Error loading reviews. Please check your permissions.', 'error');
        });
    };

    // Add a new booking
    window.addBooking = async (event) => {
        event.preventDefault();
        const form = event.target;
        const clientName = form['client_name'].value;
        const packageId = form['package-select'].value;
        const bookingDate = form['booking_date'].value;

        if (!clientName || !packageId || !bookingDate) {
            showMessage('Please fill out all fields.', 'error');
            return;
        }

        try {
            const bookingsRef = collection(db, `artifacts/${appId}/public/data/bookings`);
            await addDoc(bookingsRef, {
                clientName,
                packageId,
                date: bookingDate,
                status: 'pending',
                agentId: userId,
                createdAt: new Date().toISOString()
            });
            showMessage('Booking added successfully!', 'success');
            document.getElementById('add-booking-modal').classList.add('hidden');
            form.reset();
        } catch (e) {
            showMessage('Failed to add booking. Check console for details.', 'error');
            console.error("Error adding document: ", e);
        }
    };

    // Update booking status
    window.updateBookingStatus = async (bookingId, status) => {
        const docRef = doc(db, `artifacts/${appId}/public/data/bookings/${bookingId}`);
        try {
            await updateDoc(docRef, { status });
            showMessage(`Booking status updated to '${status}'.`, 'success');
        } catch (e) {
            showMessage('Failed to update booking status. Check console for details.', 'error');
            console.error("Error updating document: ", e);
        }
    };

    // Update review status (for moderation)
    window.updateReviewStatus = async (reviewId, status) => {
        const docRef = doc(db, `artifacts/${appId}/public/data/reviews/${reviewId}`);
        try {
            await updateDoc(docRef, { status });
            showMessage(`Review status updated to '${status}'.`, 'success');
        } catch (e) {
            showMessage('Failed to update review status. Check console for details.', 'error');
            console.error("Error updating review status: ", e);
        }
    };

    // Initial setup
    document.addEventListener('DOMContentLoaded', () => {
        initializeFirebase();
        showTab('packages');

        // Content Management Forms
        document.getElementById('add-destination-form').addEventListener('submit', window.addDestination);
        document.getElementById('add-accommodation-form').addEventListener('submit', window.addAccommodation);
        document.getElementById('add-activity-form').addEventListener('submit', window.addActivity);
        document.getElementById('add-package-form').addEventListener('submit', window.addPackage);

        // Booking Form
        document.getElementById('add-booking-form').addEventListener('submit', window.addBooking);
    });
</script>

</body>
</html>
