export default function SkeletonCard() {
  return (
    <div className="card card--skeleton" aria-hidden="true">
      <div className="card__top">
        <span className="skel" style={{ width: 72, height: 16 }} />
        <span className="skel" style={{ width: 64, height: 24, borderRadius: 9999 }} />
      </div>
      <div className="skel-stack">
        <span className="skel" style={{ width: '92%', height: 25 }} />
        <span className="skel" style={{ width: '60%', height: 25 }} />
      </div>
      <div className="skel-stack">
        <span className="skel" style={{ width: '100%', height: 16 }} />
        <span className="skel" style={{ width: '96%', height: 16 }} />
        <span className="skel" style={{ width: '70%', height: 16 }} />
      </div>
      <div className="card__bottom">
        <div className="tags">
          {[72, 88, 64].map((w, i) => (
            <span key={i} className="skel" style={{ width: w, height: 28, borderRadius: 9999 }} />
          ))}
        </div>
        <div className="card__foot">
          <div className="author">
            <span className="skel" style={{ width: 32, height: 32, borderRadius: '50%' }} />
            <div className="skel-stack">
              <span className="skel" style={{ width: 96, height: 14 }} />
              <span className="skel" style={{ width: 72, height: 14 }} />
            </div>
          </div>
          <span className="skel" style={{ width: 72, height: 14 }} />
        </div>
      </div>
    </div>
  );
}