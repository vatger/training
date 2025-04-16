// Save this as frontend/src/tailwind-test.js and import it in your main.js
// Import this in your main.js with: import './tailwind-test.js';

(function() {
  console.log("🧪 Tailwind Test Script Loaded");

  // Wait for DOM to be ready
  document.addEventListener("DOMContentLoaded", () => {
    console.log("🧪 Running Tailwind class detection tests...");
    
    // Create a test container
    const container = document.createElement("div");
    container.id = "tailwind-test-container";
    container.style.position = "fixed";
    container.style.bottom = "20px";
    container.style.right = "20px";
    container.style.zIndex = "9999";
    container.style.display = "flex";
    container.style.flexDirection = "column";
    container.style.gap = "8px";
    container.style.maxWidth = "300px";
    container.style.fontFamily = "system-ui, sans-serif";
    container.style.fontSize = "14px";
    
    // Add a title
    const title = document.createElement("div");
    title.textContent = "Tailwind Class Detection Test";
    title.style.fontWeight = "bold";
    title.style.marginBottom = "4px";
    container.appendChild(title);
    
    // Add close button
    const closeButton = document.createElement("button");
    closeButton.textContent = "×";
    closeButton.style.position = "absolute";
    closeButton.style.top = "0";
    closeButton.style.right = "5px";
    closeButton.style.background = "none";
    closeButton.style.border = "none";
    closeButton.style.fontSize = "20px";
    closeButton.style.cursor = "pointer";
    closeButton.style.color = "black";
    closeButton.onclick = () => container.remove();
    container.appendChild(closeButton);
    
    // Define test cases - each with a class and expected computed style property
    const testCases = [
      { name: "Background Color", classes: "bg-blue-500", property: "backgroundColor", expected: ["rgb(59, 130, 246)", "rgb(63, 131, 248)"] },
      { name: "Text Color", classes: "text-white", property: "color", expected: ["rgb(255, 255, 255)"] },
      { name: "Padding", classes: "p-4", property: "padding", expected: ["16px"] },
      { name: "Rounded Corners", classes: "rounded", property: "borderRadius", expected: ["0.25rem", "4px"] },
      { name: "Flex Layout", classes: "flex", property: "display", expected: ["flex"] },
      { name: "Grid Layout", classes: "grid", property: "display", expected: ["grid"] },
      { name: "Margin", classes: "m-2", property: "margin", expected: ["8px", "0.5rem"] }
    ];
    
    // Run each test
    let passCount = 0;
    testCases.forEach(test => {
      // Create test element
      const testElement = document.createElement("div");
      testElement.classList.add(...test.classes.split(" "));
      testElement.textContent = test.name;
      testElement.dataset.testProperty = test.property;
      document.body.appendChild(testElement);
      
      // Check computed style
      const computedStyle = window.getComputedStyle(testElement);
      const actualValue = computedStyle[test.property];
      const passed = test.expected.some(exp => 
        actualValue === exp || actualValue.includes(exp)
      );
      
      if (passed) passCount++;
      
      // Create result element
      const resultElement = document.createElement("div");
      resultElement.classList.add("test-result");
      resultElement.style.padding = "8px";
      resultElement.style.borderRadius = "4px";
      resultElement.style.backgroundColor = passed ? "#d1fae5" : "#fee2e2";
      resultElement.style.border = `1px solid ${passed ? "#34d399" : "#f87171"}`;
      
      resultElement.innerHTML = `
        <div style="display: flex; justify-content: space-between;">
          <span><strong>${test.name}</strong> (${test.classes})</span>
          <span>${passed ? "✅" : "❌"}</span>
        </div>
        <div style="font-size: 12px; color: #666; margin-top: 2px;">
          Expected: ${test.expected.join(" or ")}
          <br>
          Actual: ${actualValue}
        </div>
      `;
      container.appendChild(resultElement);
      
      // Remove test element
      document.body.removeChild(testElement);
    });
    
    // Add summary
    const summary = document.createElement("div");
    summary.style.marginTop = "8px";
    summary.style.fontWeight = "bold";
    summary.innerHTML = `${passCount}/${testCases.length} tests passing`;
    summary.style.color = passCount === testCases.length ? "#047857" : "#b91c1c";
    container.appendChild(summary);
    
    // Add the container to the body
    document.body.appendChild(container);
    
    console.log(`🧪 Tailwind tests completed: ${passCount}/${testCases.length} passing`);
  });
})();