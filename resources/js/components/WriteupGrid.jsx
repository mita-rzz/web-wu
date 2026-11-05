import WriteupCard from './WriteupCard.jsx';
import SkeletonCard from './SkeletonCard.jsx';

export default function WriteupGrid({ items, skeletonCount = 0 }) {
  return (
    <ul className="writeup-grid" role="list">
      {items.map((w) => (
        <li key={w.id}><WriteupCard writeup={w} /></li>
      ))}
      {Array.from({ length: skeletonCount }, (_, i) => (
        <li key={`s-${i}`}><SkeletonCard /></li>
      ))}
    </ul>
  );
}
