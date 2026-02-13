/**
 * Modal Button Diagnostic Test
 * 
 * Copy and paste this into the browser console (F12) to test modal functionality
 */

console.log("=== MODAL BUTTON DIAGNOSTIC TEST ===");

// 1. Check if Alpine.js is loaded
console.log("\n1. Checking Alpine.js...");
if (typeof Alpine === 'undefined') {
    console.error("❌ Alpine.js NOT LOADED");
} else {
    console.log("✅ Alpine.js loaded:", Alpine.version || Alpine);
}

// 2. Find the component
console.log("\n2. Looking for candidatesManager component...");
const component = document.querySelector('[x-data="candidatesManager()"]');
if (!component) {
    console.error("❌ Component not found");
} else {
    console.log("✅ Component found:", component);
}

// 3. Get Alpine data
console.log("\n3. Accessing component state...");
if (component && window.Alpine) {
    // Different Alpine versions access data differently
    let alpineData = null;
    
    // Try Alpine v3+ way
    if (component.__x) {
        alpineData = Alpine.raw(component.__x);
    } 
    // Try older Alpine way
    else if (component.__alpineData) {
        alpineData = component.__alpineData;
    }
    
    if (alpineData) {
        console.log("✅ Alpine data accessible");
        console.log("   - showImportConflictModal:", alpineData.showImportConflictModal);
        console.log("   - showImportModal:", alpineData.showImportModal);
        console.log("   - modalOpen:", alpineData.modalOpen);
        console.log("   - importMode:", alpineData.importMode);
        console.log("   - importFile:", alpineData.importFile);
    } else {
        console.error("❌ Cannot access Alpine data");
    }
} else {
    console.error("❌ Cannot access component or Alpine");
}

// 4. Test opening Import Conflict Modal
console.log("\n4. Testing modal state changes...");
if (component && window.Alpine) {
    try {
        let alpineData = null;
        if (component.__x) {
            alpineData = Alpine.raw(component.__x);
        }
        
        if (alpineData) {
            // Test setting state
            console.log("   Testing showImportConflictModal = true");
            alpineData.showImportConflictModal = true;
            
            setTimeout(() => {
                const modal = document.querySelector('[x-show="showImportConflictModal"]');
                if (modal) {
                    const displayed = window.getComputedStyle(modal).display !== 'none';
                    if (displayed) {
                        console.log("✅ Modal appears when state is set to true");
                    } else {
                        console.warn("⚠️ Modal state is true but display is hidden");
                    }
                } else {
                    console.error("❌ Modal element not found");
                }
                
                // Reset
                alpineData.showImportConflictModal = false;
            }, 100);
        }
    } catch (e) {
        console.error("❌ Error testing state changes:", e.message);
    }
}

// 5. Check for button elements
console.log("\n5. Looking for modal buttons...");
const buttons = {
    cancelImportConflicts: document.querySelector('[x-show="showImportConflictModal"] button[type="button"]:first-of-type'),
    importConflicts: document.querySelector('[x-show="showImportConflictModal"] button[type="button"]:last-of-type'),
    importCancel: document.querySelector('[x-show="showImportModal"] button[type="button"]:first-of-type'),
    importSelectFile: document.querySelector('[x-show="showImportModal"] button[type="button"]:last-of-type'),
};

Object.entries(buttons).forEach(([name, btn]) => {
    if (btn) {
        console.log(`✅ Found: ${name}`);
        console.log(`   Text: ${btn.textContent.trim()}`);
        console.log(`   type: ${btn.type}`);
        console.log(`   @click: ${btn.getAttribute('@click') || btn.getAttribute('x-on:click') || 'not found'}`);
    } else {
        console.log(`❌ Not found: ${name}`);
    }
});

// 6. Test click event
console.log("\n6. Testing button click...");
console.log("   To test, run: document.querySelector('[x-show=\"showImportConflictModal\"] button[type=\"button\"]:first-of-type').click()");

// 7. Summary
console.log("\n=== SUMMARY ===");
console.log("If you see ✅ marks, the buttons should work.");
console.log("If you see ❌ marks, there's a problem.");
console.log("\nIf buttons still don't work after all ✅:");
console.log("1. Check browser console for error messages (red text)");
console.log("2. Try hard refresh: Ctrl+F5 (Windows) or Cmd+Shift+R (Mac)");
console.log("3. Clear cache completely and refresh");
console.log("\n=== END DIAGNOSTIC ===");
