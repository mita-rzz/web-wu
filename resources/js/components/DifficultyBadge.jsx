import { DIFFICULTIES } from '../constants.js';

export default function DifficultyBadge({ difficulty }) {
  const label = DIFFICULTIES.find((d) => d.value === difficulty)?.label ?? difficulty;
  return (
    <span className={`badge badge--${difficulty}`}>
      <span className="sr-only">Level: </span>
      {label}
    </span>
  );
}