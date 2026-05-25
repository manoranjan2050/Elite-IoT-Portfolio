    <!-- Footer -->
    <footer class="py-12 border-t border-gray-900">
        <div class="container mx-auto px-6 text-center">
            <h3 class="text-2xl font-bold mb-6">MANORANJAN<span class="text-blue-500">.DEV</span></h3>
            <div class="flex justify-center gap-6 mb-8">
                <a href="https://github.com/manoranjan2050" target="_blank" class="text-2xl text-gray-500 hover:text-white transition"><i class="fa-brands fa-github"></i></a>
                <a href="#" class="text-2xl text-gray-500 hover:text-white transition"><i class="fa-brands fa-linkedin"></i></a>
                <a href="#" class="text-2xl text-gray-500 hover:text-white transition"><i class="fa-brands fa-twitter"></i></a>
                <a href="login.php" class="text-2xl text-gray-500 hover:text-white transition"><i class="fa-solid fa-user-lock"></i></a>
            </div>
            <p class="text-gray-600 mb-2">© 2024 Manoranjan. All rights reserved.</p>
            <p class="text-gray-500 text-sm flex items-center justify-center gap-2">
                <img src="https://github.com/manoranjan2050.png" class="w-6 h-6 rounded-full" alt="Manoranjan">
                Designed & Developed by <a href="https://github.com/manoranjan2050" target="_blank" class="text-blue-500 hover:underline">Manoranjan</a>
            </p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ once: true });

        // IoT Lab Temp Simulation
        const tempElement = document.getElementById('lab-temp');
        if(tempElement) {
            setInterval(() => {
                const currentTemp = 23 + (Math.random() * 3);
                tempElement.textContent = `${currentTemp.toFixed(1)}°C`;
            }, 5000);
        }

        // GitHub Repo Fetch
        const githubUsername = "manoranjan2050"; 
        async function fetchRepos() {
            const container = document.getElementById("repo-container");
            if(!container) return;
            try {
                const response = await fetch(`https://api.github.com/users/${githubUsername}/repos?sort=updated&per_page=6`);
                const repos = await response.json();
                container.innerHTML = "";
                
                repos.forEach(repo => {
                    if(!repo.fork) {
                        const div = document.createElement("div");
                        div.className = "glass p-6 rounded-xl hover:bg-gray-900 transition border border-gray-800 group";
                        div.innerHTML = `
                            <div class="flex justify-between items-start mb-4">
                                <i class="fa-solid fa-book-bookmark text-blue-500"></i>
                                <span class="text-xs bg-gray-800 px-2 py-1 rounded text-gray-400">${repo.language || 'Code'}</span>
                            </div>
                            <h4 class="text-xl font-bold mb-2 group-hover:text-blue-400 transition">${repo.name}</h4>
                            <p class="text-gray-500 text-sm mb-4 line-clamp-2">${repo.description || 'No description available.'}</p>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-600"><i class="fa-solid fa-star mr-1"></i>${repo.stargazers_count}</span>
                                <a href="${repo.html_url}" target="_blank" class="text-sm font-bold hover:underline">View Repo <i class="fa-solid fa-external-link text-xs ml-1"></i></a>
                            </div>
                        `;
                        container.appendChild(div);
                    }
                });
            } catch (error) {
                console.error("Error fetching repos:", error);
                container.innerHTML = "<p class='col-span-full text-center text-gray-500'>Failed to load repositories.</p>";
            }
        }
        fetchRepos();
    </script>
</body>
</html>
