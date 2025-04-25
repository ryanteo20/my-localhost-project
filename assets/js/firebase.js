import { initializeApp } from "firebase/app";
import { getStorage, ref, uploadBytes } from "firebase/storage";

// Your Firebase configuration with storageBucket
const firebaseConfig = {
  // ...
  storageBucket: 'gs://fyp---2200560.appspot.com'
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);

// Get a reference to the Firebase Storage service
const storage = getStorage(app);

// Create a reference to the file you want to upload
const fileRef = ref(storage, 'test-file.txt');

// Create a new Blob with some sample data
const fileContent = new Blob(['This is a test file'], { type: 'text/plain' });

// Upload the file to Firebase Storage
uploadBytes(fileRef, fileContent)
  .then((snapshot) => {
    console.log('File uploaded successfully!');
    console.log('Download URL:', snapshot.metadata.fullPath);
  })
  .catch((error) => {
    console.error('Error uploading file:', error);
  });