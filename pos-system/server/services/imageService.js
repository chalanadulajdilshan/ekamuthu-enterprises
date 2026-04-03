const fs = require('fs');
const path = require('path');

const UPLOAD_DIR = path.join(__dirname, '../../uploads');

/**
 * Save a base64-encoded image to the uploads directory.
 * @param {string} base64String - The full base64 data URI string.
 * @param {string} identifier - A label used in the filename (e.g. product code).
 * @returns {string|null} The saved filename, or null if nothing was saved.
 */
const saveBase64Image = (base64String, identifier) => {
    if (!base64String || !base64String.includes('base64,')) return null;

    try {
        const base64Data = base64String.split('base64,')[1];
        const filename = `item_${identifier}_${Date.now()}.jpg`;
        const uploadPath = path.join(UPLOAD_DIR, filename);

        // Ensure the uploads directory exists
        if (!fs.existsSync(UPLOAD_DIR)) {
            fs.mkdirSync(UPLOAD_DIR, { recursive: true });
        }

        fs.writeFileSync(uploadPath, base64Data, 'base64');
        return filename;
    } catch (error) {
        console.error('Error saving image:', error);
        return null;
    }
};

/**
 * Delete an image file from the uploads directory.
 * @param {string} filename - The filename to delete.
 */
const deleteImage = (filename) => {
    if (!filename) return;
    try {
        const filePath = path.join(UPLOAD_DIR, filename);
        if (fs.existsSync(filePath)) {
            fs.unlinkSync(filePath);
        }
    } catch (error) {
        console.error('Error deleting image:', error);
    }
};

module.exports = { saveBase64Image, deleteImage };
