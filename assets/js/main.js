document.getElementById("copyButton").addEventListener("click", async function () {
    const textToCopy = document.querySelector(".content-item-code").innerText;

    try {
        await navigator.clipboard.writeText(textToCopy);
        alert("Block copied to clipboard");
    } catch (err) {
        console.error('Failed to copy: ', err);
        alert("Failed to copy block to clipboard");
    }
});
