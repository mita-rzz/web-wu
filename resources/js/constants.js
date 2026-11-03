// TODO: ganti dengan fetch ke backend PHP bila opsi filter dinamis dari database.
export const CATEGORIES = ['Web', 'Crypto', 'Pwn', 'Forensics', 'Mobile'];

export const DIFFICULTIES = [
  { value: 'easy', label: 'Easy' },
  { value: 'medium', label: 'Medium' },
  { value: 'hard', label: 'Hard' },
  { value: 'insane', label: 'Insane' },
];

export const CHALLENGE_TAGS = ['SQLi', 'XSS', 'RCE', 'SSRF', 'IDOR', 'ROP', 'JWT', 'RSA'];
export const TOOL_TAGS = ['BurpSuite', 'Ghidra', 'GDB', 'Wireshark', 'Volatility', 'Frida', 'pwntools', 'CyberChef'];

export const tagLabel = (t) => (CHALLENGE_TAGS.includes(t) ? `#${t}` : t);