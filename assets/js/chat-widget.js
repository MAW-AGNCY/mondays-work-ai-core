// Chat Widget JavaScript
jQuery(document).ready(function($) {
  var widget = $('#maw-chat-widget');
  var bubble = $('.maw-chat-bubble');
  var messages = $('.maw-chat-messages');
  var input = $('.maw-chat-input');
  var sendBtn = $('.maw-chat-send');
  var toggleBtn = $('.maw-chat-toggle');

  // Toggle chat widget
  bubble.on('click', function() {
    widget.removeClass('maw-chat-minimized');
  });

  toggleBtn.on('click', function() {
    widget.addClass('maw-chat-minimized');
  });

  // Send message
  function sendMessage() {
    var message = input.val().trim();
    if (!message) return;

    // Add user message
    addMessage(message, 'user');
    input.val('');

    // Send to server
    $.ajax({
      url: mawChat.ajaxUrl,
      type: 'POST',
      data: {
        action: 'maw_chat_message',
        nonce: mawChat.nonce,
        message: message
      },
      success: function(response) {
        if (response.success) {
          addMessage(response.data.response, 'bot');
        } else {
          addMessage('Error: ' + response.data.message, 'bot');
        }
      },
      error: function() {
        addMessage('Connection error. Please try again.', 'bot');
      }
    });
  }

  // Add message to chat
  function addMessage(text, type) {
    var msg = $('<div></div>')
      .addClass('maw-chat-message')
      .addClass(type)
      .text(text);
    messages.append(msg);
    messages.scrollTop(messages[0].scrollHeight);
  }

  // Send on button click
  sendBtn.on('click', sendMessage);

  // Send on Enter key
  input.on('keypress', function(e) {
    if (e.which === 13) {
      sendMessage();
    }
  });

  // Initial welcome message
  addMessage('Hello! How can I help you today?', 'bot');
});
