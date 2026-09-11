import { initializeApp } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-app.js";
import { getAuth, GoogleAuthProvider, signInWithPopup, signInWithEmailAndPassword, createUserWithEmailAndPassword, sendPasswordResetEmail } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-auth.js";

const firebaseConfig = {
  apiKey: "AIzaSyAm2SgYZBAY6vgBN8kXHxKUhh2PDz7olD0",
  authDomain: "xanarchy-store.firebaseapp.com",
  projectId: "xanarchy-store",
  storageBucket: "xanarchy-store.firebasestorage.app",
  messagingSenderId: "1049687017579",
  appId: "1:1049687017579:web:a47ad68f5d8cd53ed2492e",
  measurementId: "G-QRE98L44N4"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
const googleProvider = new GoogleAuthProvider();

export { auth, googleProvider, signInWithPopup, signInWithEmailAndPassword, createUserWithEmailAndPassword, sendPasswordResetEmail };
