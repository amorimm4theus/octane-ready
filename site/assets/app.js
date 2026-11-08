// Copy buttons: <button class="copy" data-copy="#id"> copies the text of #id.
document.querySelectorAll('[data-copy]').forEach((button) => {
  const label = button.textContent;
  button.addEventListener('click', async () => {
    const source = document.querySelector(button.dataset.copy);
    const text = (source.dataset.text || source.innerText).trim();
    try {
      await navigator.clipboard.writeText(text);
      button.textContent = button.dataset.done || 'Copied';
      button.classList.add('done');
    } catch {
      button.textContent = button.dataset.fail || 'Select and copy';
    }
    setTimeout(() => { button.textContent = label; button.classList.remove('done'); }, 1800);
  });
});

// Live star count from the public GitHub API. The page works without it.
fetch('https://api.github.com/repos/amorimm4theus/octane-ready')
  .then((response) => (response.ok ? response.json() : null))
  .then((repo) => {
    if (!repo || typeof repo.stargazers_count !== 'number') return;
    document.querySelectorAll('[data-stars]').forEach((el) => {
      el.textContent = repo.stargazers_count.toLocaleString();
      el.hidden = false;
    });
  })
  .catch(() => {});

// Install tabs.
document.querySelectorAll('[role="tablist"]').forEach((list) => {
  const tabs = list.querySelectorAll('[role="tab"]');
  tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      tabs.forEach((other) => {
        const selected = other === tab;
        other.setAttribute('aria-selected', String(selected));
        document.getElementById(other.getAttribute('aria-controls')).hidden = !selected;
      });
    });
  });
});
