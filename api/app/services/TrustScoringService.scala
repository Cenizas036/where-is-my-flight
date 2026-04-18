package services

import javax.inject._
import play.api.{Configuration, Logging}
import play.api.libs.json._
import scala.concurrent.{ExecutionContext, Future}

/**
 * TrustScoringService — Calculates and manages trust scores for
 * community gate contributors.
 *
 * Trust Score Components:
 * ─────────────────────────────────────────────────────────────
 * 1. Accuracy Rate (70% weight)
 *    - Ratio of verified to total contributions
 *    - Penalizes heavily for disputed/rejected edits
 *
 * 2. Recency Weight (20% weight)
 *    - Recent accuracy matters more than historical
 *    - Uses an exponential decay: e^(-λ * days_since_contribution)
 *    - Window: last 30 days have full weight, decays after
 *
 * 3. Volume Bonus (10% weight)
 *    - Small bonus for users with many contributions
 *    - Caps at 0.05 to prevent volume-gaming
 *    - Formula: min(0.05, total_contributions * 0.001)
 *
 * Auto-Approval Logic:
 * ─────────────────────────────────────────────────────────────
 * - Score >= 0.85 → Auto-approve, goes live immediately
 * - Score >= 0.65 → Goes live tentatively, awaits corroboration
 * - Score <  0.65 → Enters moderation queue
 * - Score <  0.30 → User flagged for review (possible abuse)
 *
 * Corroboration:
 * ─────────────────────────────────────────────────────────────
 * - Each "agree" from another user: +0.08 to contribution confidence
 * - Each "dispute" from another user: -0.12 to contribution confidence
 * - Asymmetric penalty ensures bad data gets caught faster
 * - 3+ corroborations within 15 minutes → high confidence
 */
@Singleton
class TrustScoringService @Inject()(
  config: Configuration
)(implicit ec: ExecutionContext) extends Logging {

  private val autoApproveThreshold = config.getOptional[Double]("wimf.trust.auto-approve").getOrElse(0.85)
  private val liveThreshold = config.getOptional[Double]("wimf.trust.live").getOrElse(0.65)
  private val corroborationWindow = config.getOptional[Int]("wimf.trust.corroboration-window").getOrElse(15)
  private val minCorroborations = config.getOptional[Int]("wimf.trust.min-corroborations").getOrElse(3)

  /**
   * Calculate the composite trust score for a user.
   */
  def calculateCompositeScore(
    accuracyRate: Double,
    recencyWeight: Double,
    totalContributions: Int
  ): Double = {
    val accuracy = accuracyRate * 0.70
    val recency = recencyWeight * 0.20
    val volume = Math.min(0.05, totalContributions * 0.001) * 0.10 / 0.05 * 0.10

    val composite = accuracy + recency + volume
    Math.min(1.0, Math.max(0.0, composite))
  }

  /**
   * Calculate initial confidence for a new gate contribution
   * based on the contributor's trust score.
   */
  def calculateInitialConfidence(compositeScore: Double, totalContributions: Int): Double = {
    val base = compositeScore
    val volumeBonus = Math.min(0.05, totalContributions * 0.001)
    Math.min(1.0, base + volumeBonus)
  }

  /**
   * Recalculate contribution confidence after a corroboration event.
   *
   * @param currentConfidence Current confidence score
   * @param agrees            Number of agree votes
   * @param disputes          Number of dispute votes
   * @return Updated confidence score
   */
  def recalculateConfidence(currentConfidence: Double, agrees: Int, disputes: Int): Double = {
    val boostPerAgree = 0.08
    val penaltyPerDispute = 0.12

    val adjustment = (agrees * boostPerAgree) - (disputes * penaltyPerDispute)
    val newConfidence = currentConfidence + adjustment

    Math.min(1.0, Math.max(0.0, newConfidence))
  }

  /**
   * Determine the moderation action for a contribution.
   */
  def determineModerationAction(confidence: Double): ModerationAction = {
    if (confidence >= autoApproveThreshold) ModerationAction.AutoApprove
    else if (confidence >= liveThreshold) ModerationAction.TentativeLive
    else if (confidence >= 0.30) ModerationAction.EnqueueForReview
    else ModerationAction.FlagUser
  }

  /**
   * Calculate recency weight using exponential decay.
   * Recent contributions count more toward the trust score.
   */
  def calculateRecencyWeight(daysSinceLastContribution: Int): Double = {
    val lambda = 0.05 // decay rate
    Math.exp(-lambda * Math.max(0, daysSinceLastContribution - 7))
  }
}

/**
 * Moderation actions based on trust score.
 */
sealed trait ModerationAction
object ModerationAction {
  case object AutoApprove extends ModerationAction
  case object TentativeLive extends ModerationAction
  case object EnqueueForReview extends ModerationAction
  case object FlagUser extends ModerationAction
}
