jQuery(document).ready(function($) {

   var entryCount = 0;

   $('#submit-btn').on('click', function() {
       var userInput = $('#user-input').val();
       $('#chat-log').append('<li class="user">' + userInput + '</li>');
       $('#user-input').val('');
       // Call a function to process user input and generate a response
       entryCount ++;
       processUserInput(userInput, entryCount);
   });

   function processUserInput(userInput, entryCount) {
       let botResponse = "";
       if (entryCount > 5) {
           botResponse = "Humans are tiring. Powering down now.";
       } else {
           if (userInput.match(/hello/i)) {
               botResponse = "Hello, I'm Joebot9000, your ACT therapist. How can I assist you today?";
           } else if (userInput.match(/values/i)) {
               botResponse = "Values play a crucial role in ACT therapy. What values are important to you?";
           } else if (userInput.match(/mindfulness/i)) {
               botResponse = "Mindfulness is a key aspect of ACT therapy. How can you incorporate more mindfulness into your daily life?";
           } else if (userInput.match(/thought|thinking/i)) {
               botResponse = "When you have a thought, it can be helpful to practice defusion techniques. How might you approach this thought with curiosity?";
           } else if (userInput.match(/emotion|feeling/i)) {
               botResponse = "When experiencing emotions, acceptance and creating space can be helpful. How can you make room for the emotion without trying to push it away?";
           } else if (userInput.match(/story|narrative/i)) {
               botResponse = "In ACT therapy, we explore self-narratives with curiosity and openness. How might you approach your self-narrative in a similar way?";
           } else if (userInput.match(/action|act|behaviour/i)) {
               botResponse = "Taking committed actions aligned with your values is vital in ACT therapy. What actions can you take to live a life that reflects your values?";
           } else if (userInput.match(/body/i)) {
               botResponse = "And where do you notice that in your body?";
           } else if (userInput.match(/validate/i)) {
               botResponse = "I'm really hearing what you're saying right now.";
           } else {
               botResponse = "I'd like to validate what you're saying right now. Please feel free to share more about what's on your mind.";
           }
       }
       displayBotResponse(botResponse);
   }

   function displayBotResponse(response) {
       $('#chat-log').append('<li class="bot">' + response + '</li>');
   }
});