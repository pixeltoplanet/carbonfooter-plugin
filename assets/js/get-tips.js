document.addEventListener('DOMContentLoaded', function() {
  const fetchTips = async () => {
    try {
      // Use WordPress AJAX to proxy the request through the server
      // This avoids CORS issues by making the request from the server side
      const response = await fetch(ajaxurl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          action: 'carbonfooter_fetch_tips',
        })
      });

      if (!response.ok) {
        throw new Error('Network response was not ok: ' + response.status);
      }

      const data = await response.json();
      
      if (data.success && data.data) {
        console.log(data.data);
        // Here we can process the tips data
        displayTips(data.data);
      } else {
        console.error('Error in AJAX response:', data.data);
      }
    } catch (error) {
      console.error('Error fetching tips:', error);
    }
  };

  // Function to display tips in the UI
  const displayTips = (tips) => {
    const tipsContainer = document.getElementById('carbonfooter-tips-container');
    if (!tipsContainer) return;
    
    // Clear any existing content
    tipsContainer.innerHTML = '';
    
    // Add tips to the container
    if (Array.isArray(tips) && tips.length > 0) {
      tips.forEach(tip => {
        const tipElement = document.createElement('div');
        tipElement.className = 'carbonfooter-tip';
        tipElement.innerHTML = `
          <h3>${tip.title || 'Tip'}</h3>
          <div class="tip-content">${tip.content || ''}</div>
        `;
        tipsContainer.appendChild(tipElement);
      });
    } else {
      tipsContainer.innerHTML = '<p>No tips available at the moment.</p>';
    }
  };

  fetchTips();
});