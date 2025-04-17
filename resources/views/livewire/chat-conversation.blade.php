<div>
    <div class="bg-white rounded-lg shadow-lg h-screen">
        @if ($telegramUser)
            <div x-data x-init="$nextTick(() => {
                const messageContainer = document.getElementById('chat-messages');
                if (messageContainer) {
                    messageContainer.scrollTop = messageContainer.scrollHeight;
                }
            })">
                <!-- Chat Header -->
                <div class="p-4 border-b flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                            <span class="text-white font-semibold">{{ substr($telegramUser->first_name, 0, 1) }}</span>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">
                            {{ $telegramUser->first_name ?: 'Unknown' }} {{ $telegramUser->last_name ?: '' }} <svg class="inline-block w-4 h-4 mb-0.5 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        </h3>
                        <p class="text-sm text-gray-500">
                            @if($telegramUser->username)
                                {{ '@' . $telegramUser->username }}
                            @else
                                unknown
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Chat Messages -->
                <div class="h-[calc(100vh-10rem)] overflow-y-auto p-4 space-y-4" id="chat-messages" wire:key="messages-container">
                    @php
                        $processedGroups = [];
                    @endphp
                    
                    @foreach ($messages as $message)
                        @if (
                            !isset($message['media_group_id']) || 
                            !in_array($message['media_group_id'], $processedGroups)
                        )
                            <div class="flex {{ $message['sender'] == 'admin' ? 'justify-end' : 'justify-start' }}"
                                wire:key="message-{{ $loop->index }}">
                                <div class="max-w-[70%] {{ $message['sender'] == 'admin' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-900' }} rounded-lg px-2 py-2 shadow">
                                    @if (isset($message['media_group_id']) && $message['file_type'] === 'photo')
                                        <div class="grid grid-cols-2 gap-1 mb-2">
                                            @foreach ($message['group_files'] as $groupFile)
                                                <div class="relative aspect-square">
                                                    <img src="{{ $groupFile['file_url'] }}" 
                                                        alt="Shared image" 
                                                        class="rounded-lg w-full h-full object-cover cursor-pointer"
                                                        onclick="window.open('{{ $groupFile['file_url'] }}', '_blank')"
                                                        loading="lazy">
                                                </div>
                                            @endforeach
                                        </div>
                                        @php
                                            $processedGroups[] = $message['media_group_id'];
                                        @endphp
                                    @elseif (isset($message['file_url']) && $message['file_type'] === 'photo' && !isset($message['media_group_id']))
                                        <div class="">
                                            <img src="{{ $message['file_url'] }}" 
                                                alt="Shared image" 
                                                class="rounded-lg max-w-full md:max-w-[400px] h-auto cursor-pointer"
                                                onclick="window.open('{{ $message['file_url'] }}', '_blank')"
                                                loading="lazy">
                                        </div>
                                    @elseif (isset($message['file_url']) && $message['file_type'] === 'document')
                                        <div class="document-container flex items-center gap-2 bg-white/10 rounded-md p-2">
                                            <div class="document-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <div class="flex-1">
                                                <div class="text-sm font-medium">{{ $message['filename'] ?? 'Document' }}</div>
                                                <a href="{{ $message['file_url'] }}" 
                                                    target="_blank" 
                                                    class="text-xs underline opacity-75 hover:opacity-100">
                                                    Download Document
                                                </a>
                                            </div>
                                        </div>
                                    @elseif (isset($message['file_url']) && $message['file_type'] === 'photo' && !isset($message['media_group_id']))
                                        <div class="">
                                            <img src="{{ $message['file_url'] }}" 
                                                alt="Shared image" 
                                                class="rounded-lg max-w-full md:max-w-[400px] h-auto cursor-pointer"
                                                onclick="window.open('{{ $message['file_url'] }}', '_blank')"
                                                loading="lazy">
                                        </div>
                                    @elseif (isset($message['file_url']) && $message['file_type'] === 'video')
                                        <div class="video-container relative">
                                            <video 
                                                class="rounded-lg max-w-full md:max-w-[300px] h-auto"
                                                controls
                                                controlsList="nodownload"
                                                poster="{{ $message['thumbnail_url'] ?? '' }}"
                                                preload="none">
                                                <source src="{{ $message['file_url'] }}" type="{{ $message['mime_type'] ?? 'video/mp4' }}">
                                                Your browser does not support the video tag.
                                            </video>
                                        </div>
                                    @elseif (isset($message['file_url']) && $message['file_type'] === 'document')
                                        <div class="document-container flex items-center gap-2 bg-white/10 rounded-md p-2">
                                            <div class="document-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <div class="flex-1">
                                                <div class="text-sm font-medium">{{ $message['filename'] ?? pathinfo($message['file_url'], PATHINFO_BASENAME) }}</div>
                                                <a href="{{ $message['file_url'] }}" 
                                                   download="{{ $message['filename'] ?? pathinfo($message['file_url'], PATHINFO_BASENAME) }}"
                                                   target="_blank" 
                                                   class="text-xs underline opacity-75 hover:opacity-100">
                                                    Download Document
                                                </a>
                                            </div>
                                        </div>
                                    @elseif (isset($message['file_url']) && $message['file_type'] === 'voice')
                                        <div class="audio-message flex items-center gap-3 min-w-[240px]">
                                            <button 
                                                class="play-pause-btn w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center hover:bg-blue-700 transition-colors"
                                                onclick="toggleAudioPlayback(this, '{{ $message['file_url'] }}')"
                                                data-playing="false">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                                                </svg>
                                            </button>
                                            <div class="flex-1">
                                                <div class="waveform bg-blue-100 py-1 h-[30px] rounded-lg relative overflow-hidden">
                                                    <div class="waveform-bars flex items-center h-full px-2 space-x-[2px]">
                                                        @for ($i = 0; $i < 40; $i++)
                                                            @php $height = rand(20, 100); @endphp
                                                            <div class="bar w-[3px] bg-blue-300" style="height: {{ $height }}%"></div>
                                                        @endfor
                                                    </div>
                                                    <div class="progress absolute top-0 left-0 h-full bg-blue-500/20 pointer-events-none" style="width: 0%"></div>
                                                </div>
                                                <div class="flex justify-between text-xs text-gray-500 mt-1">
                                                    <span class="duration">0:00</span>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Voice message content -->
                                    @endif
                                    
                                    @if (!empty($message['message']) && (!isset($message['file_type']) || $message['file_type'] !== 'voice'))
                                        <p class="text-sm">{{ $message['message'] }}</p>
                                    @endif
                                    <p class="text-xs text-end {{ $message['sender'] == 'admin' ? 'text-blue-100' : 'text-gray-400' }} mt-1">
                                        {{ $message['created_at']->format('h:i A') }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Chat Input -->
            <div class="p-4 border-t" x-data="{ recording: false, audioBlob: null, hasText: false }" @message-sent.window="hasText = false">
                <form wire:submit.prevent="sendMessage" x-on:submit="hasText = false" class="flex space-x-2">
                    <input type="text" 
                        wire:model.live="newMessage" 
                        x-on:input="hasText = $event.target.value.length > 0"
                        @keydown.enter="hasText = false"
                        class="flex-1 rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Type your message..." 
                        autocomplete="off" />
                    
                    <!-- Photo Upload Button -->
                    <!-- Upload Button with Options -->
                    <div class="relative" x-data="{ showOptions: false }">
                        <!-- Hidden File Inputs -->
                        <input type="file" 
                            wire:model.live="uploadedFile" 
                            accept="image/*"
                            class="hidden" 
                            id="photo-upload" />
                        <input type="file" 
                            wire:model.live="uploadedDocument" 
                            accept=".pdf,.doc,.docx,.txt,.xls,.xlsx"
                            class="hidden" 
                            id="file-upload" />

                        <!-- Upload Button with Dropdown -->
                        <div class="relative">
                            <button type="button"
                                @click="showOptions = !showOptions"
                                class="inline-flex items-center justify-center px-4 py-4 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M12.232 4.232a2.5 2.5 0 013.536 3.536l-1.225 1.224a.75.75 0 001.061 1.06l1.224-1.224a4 4 0 00-5.656-5.656l-3 3a4 4 0 00.225 5.865.75.75 0 00.977-1.138 2.5 2.5 0 01-.142-3.667l3-3z" />
                                    <path d="M11.603 7.963a.75.75 0 00-.977 1.138 2.5 2.5 0 01.142 3.667l-3 3a2.5 2.5 0 01-3.536-3.536l1.225-1.224a.75.75 0 00-1.061-1.06l-1.224 1.224a4 4 0 105.656 5.656l3-3a4 4 0 00-.225-5.865z" />
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div x-show="showOptions"
                                @click.away="showOptions = false"
                                class="absolute bottom-full right-0 mb-2 w-40 bg-white rounded-lg shadow-lg overflow-hidden">
                                <label for="photo-upload" 
                                    class="flex items-center px-4 py-2 hover:bg-gray-100 cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-sm text-gray-700">Upload Photo</span>
                                </label>
                                <label for="file-upload" 
                                    class="flex items-center px-4 py-2 hover:bg-gray-100 cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-sm text-gray-700">Upload File</span>
                                </label>
                            </div>
                        </div>
                    </div>
            
                    <!-- Voice Input Button -->
                    <button type="button" 
                        x-show="!recording && !hasText"
                        x-cloak
                        @click="startRecording()"
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                            <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                        </svg>
                    </button>
            
                    <!-- Send Message Button -->
                    <button type="submit"
                        x-show="hasText"
                        x-cloak
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                        </svg>
                    </button>
                </form>
            </div>
            
            <script>
                function startRecording() {
                    navigator.mediaDevices.getUserMedia({ audio: true })
                        .then(stream => {
                            const mediaRecorder = new MediaRecorder(stream);
                            const audioChunks = [];
                            
                            mediaRecorder.addEventListener("dataavailable", event => {
                                audioChunks.push(event.data);
                            });
        
                            mediaRecorder.addEventListener("stop", () => {
                                const audioBlob = new Blob(audioChunks, { type: 'audio/ogg' });
                                this.audioBlob = audioBlob;
                                
                                // Stop all tracks
                                stream.getTracks().forEach(track => track.stop());
                                
                                // Send to server
                                Livewire.dispatch('sendVoiceMessage', { audio: audioBlob });
                            });
        
                            mediaRecorder.start();
                            this.recording = true;
                            this.mediaRecorder = mediaRecorder;
                        });
                }
            
                function cancelRecording() {
                    if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                        this.mediaRecorder.stream.getTracks().forEach(track => track.stop());
                        this.mediaRecorder.stop();
                    }
                    this.recording = false;
                    this.audioBlob = null;
                }
            
                function stopAndSendRecording() {
                    if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                        this.mediaRecorder.stop();
                    }
                    this.recording = false;
                }
            </script>
        @else
            <div class="h-full flex items-center justify-center">
                <p class="text-gray-500">Select a user to start chatting</p>
            </div>
        @endif
    </div>

    <!-- Move scripts inside the root div -->
    <script>
        // Audio playback functions
        function toggleAudioPlayback(button, audioUrl) {
            let audio = button.audioElement;
            
            if (!audio) {
                audio = new Audio(audioUrl);
                button.audioElement = audio;
                
                audio.addEventListener('timeupdate', () => {
                    const progress = (audio.currentTime / audio.duration) * 100;
                    const progressBar = button.parentElement.querySelector('.progress');
                    const durationElement = button.parentElement.querySelector('.duration');
                    if (progressBar) progressBar.style.width = `${progress}%`;
                    if (durationElement) durationElement.textContent = formatTime(audio.currentTime);
                });
                
                audio.addEventListener('ended', () => {
                    button.dataset.playing = 'false';
                    button.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                    </svg>`;
                });
            }
            
            if (button.dataset.playing === 'true') {
                audio.pause();
                button.dataset.playing = 'false';
                button.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                </svg>`;
            } else {
                audio.play();
                button.dataset.playing = 'true';
                button.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9 8a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1zm4 0a1 1 0 011-1h.01a1 1 0 110 2H14a1 1 0 01-1-1z" clip-rule="evenodd"/>
                </svg>`;
            }
        }

        function formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
        }

        // Recording functions
        function startRecording() {
            // Voice recording logic
            navigator.mediaDevices.getUserMedia({ audio: true })
                .then(stream => {
                    const mediaRecorder = new MediaRecorder(stream);
                    const audioChunks = [];
                    
                    mediaRecorder.addEventListener("dataavailable", event => {
                        audioChunks.push(event.data);
                    });
        
                    mediaRecorder.addEventListener("stop", () => {
                        const audioBlob = new Blob(audioChunks, { type: 'audio/ogg' });
                        const formData = new FormData();
                        formData.append('audio', audioBlob);
                        
                        // Send to server
                        Livewire.dispatch('sendVoiceMessage', { audio: audioBlob });
                    });
        
                    mediaRecorder.start();
                    this.recording = true;
                    this.mediaRecorder = mediaRecorder;
                });
        }

        function stopRecording() {
            this.mediaRecorder.stop();
            this.recording = false;
        }

        // Scroll handling
        document.addEventListener('livewire:initialized', () => {
            const scrollToBottom = () => {
                const messageContainer = document.getElementById('chat-messages');
                if (messageContainer) {
                    setTimeout(() => {
                        messageContainer.scrollTop = messageContainer.scrollHeight;
                    }, 200);
                }
            };

            // When conversation is loaded
            Livewire.on('conversationLoaded', () => {
                scrollToBottom();
            });

            // When message is sent or received
            Livewire.on('messageSent', scrollToBottom);
            Livewire.on('messageReceived', scrollToBottom);

            // Watch for changes in the messages container
            const observer = new MutationObserver(scrollToBottom);
            const messageContainer = document.getElementById('chat-messages');
            if (messageContainer) {
                observer.observe(messageContainer, { 
                    childList: true,
                    subtree: true 
                });
            }
        });
    </script>

    <!-- Add this style -->
    <style>
        [x-cloak] { display: none !important; }
    </style>
</div>
